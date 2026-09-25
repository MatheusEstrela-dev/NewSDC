<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\Rastro;
use App\Modules\Resgate\Support\TrilhaDoPedido;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Execucao do pedido aprovado - Fase 4 (plano, secao 4).
 *
 *   APROVADO       --termo (CEDEC: processo SEI + PDF)-->          TERMO_EMITIDO
 *   TERMO_EMITIDO  --assinar pelo Estado E pelo municipio-->       TERMO_ASSINADO
 *   TERMO_ASSINADO --entregar (unidade responsavel + evidencias)--> ENTREGUE
 *   ENTREGUE       --confirmar (municipio)-->                      CONCLUIDO (debito definitivo)
 *   ENTREGUE       --contestar (municipio)-->                      CONTESTADO --entregar--> ENTREGUE
 *
 * O SDC nao assina: o termo e assinado no SEI, e cada parte registra aqui a
 * sua assinatura com o numero do documento SEI. Nenhuma transferencia sem
 * processo SEI (premissa P3). Segregacao de funcoes no servico e no banco.
 *
 * O debito so vira definitivo na CONFIRMACAO do municipio: entrega nao
 * confirmada nao consome ponto nenhum.
 */
final class ExecucaoDoResgate
{
    public function __construct(
        private readonly TrilhaDoPedido $trilha,
        private readonly MovimentosDaCarteira $movimentos,
        private readonly DocumentosDoPedido $documentos,
    ) {}

    public function emitirTermo(int $pedidoId, string $processoSei, string $documentoSei, UploadedFile $termo, int $atorId, Rastro $rastro): void
    {
        $this->emTransacao(function (Connection $db) use ($pedidoId, $processoSei, $documentoSei, $termo, $atorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['aprovado']);
            $this->exigirOutraPessoa($pedido, $atorId, 'Quem solicitou não emite o termo.');

            $this->documentos->anexar($db, $pedidoId, 'termo', $termo, $atorId, $rastro);
            $this->trilha->mudarStatus($db, $pedidoId, 'termo_emitido', ['processo_sei' => $processoSei, 'termo_documento_sei' => $documentoSei]);
            $this->trilha->registrar($db, $pedidoId, 'termo', 'aprovado', 'termo_emitido', $atorId, 'resgate.aprovar',
                "Processo SEI {$processoSei}; termo documento SEI {$documentoSei}.", $rastro);
        });
    }

    /** @param 'estado'|'municipio' $parte */
    public function assinar(int $pedidoId, string $parte, string $documentoSei, int $atorId, Rastro $rastro): void
    {
        $this->emTransacao(function (Connection $db) use ($pedidoId, $parte, $documentoSei, $atorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['termo_emitido']);
            if ($parte === 'estado') {
                $this->exigirOutraPessoa($pedido, $atorId, 'Quem solicitou não assina pelo Estado.');
            }
            $coluna = $parte === 'estado' ? 'assinado_estado' : 'assinado_municipio';
            if ($pedido->{"{$coluna}_por"} !== null) {
                throw new RegraDoResgate('Esta parte já registrou a assinatura.', 'pedido');
            }

            $outraAssinou = $parte === 'estado' ? $pedido->assinado_municipio_por !== null : $pedido->assinado_estado_por !== null;
            $novo = $outraAssinou ? 'termo_assinado' : 'termo_emitido';
            $this->trilha->mudarStatus($db, $pedidoId, $novo, ["{$coluna}_por" => $atorId, "{$coluna}_em" => 'now()']);
            $this->trilha->registrar($db, $pedidoId, "assinar_{$parte}", 'termo_emitido', $novo, $atorId,
                $parte === 'estado' ? 'resgate.aprovar' : 'resgate.solicitar',
                'Assinatura registrada no SEI, documento ' . $documentoSei . '.', $rastro);
        });
    }

    /** @param list<UploadedFile> $evidencias */
    public function entregar(int $pedidoId, array $evidencias, string $observacao, int $atorId, Rastro $rastro): void
    {
        if ($evidencias === []) {
            throw new RegraDoResgate('A entrega exige ao menos uma evidência (termo de recebimento, foto).', 'evidencias');
        }

        $this->emTransacao(function (Connection $db) use ($pedidoId, $evidencias, $observacao, $atorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['termo_assinado', 'contestado']);
            $this->exigirOutraPessoa($pedido, $atorId, 'Quem solicitou não registra a entrega.');
            $aprovador = $db->selectOne("SELECT 1 FROM resgate.pedido_eventos WHERE pedido_id = ? AND etapa = 'aprovar' AND ator_user_id = ?", [$pedidoId, $atorId]);
            if ($aprovador !== null) {
                throw new RegraDoResgate('Quem aprovou o pedido não registra a entrega.', 'pedido');
            }

            foreach ($evidencias as $arquivo) {
                $this->documentos->anexar($db, $pedidoId, 'evidencia_entrega', $arquivo, $atorId, $rastro);
            }
            $this->trilha->mudarStatus($db, $pedidoId, 'entregue', ['entregue_em' => 'now()']);
            $this->trilha->registrar($db, $pedidoId, 'entregar', (string) $pedido->status, 'entregue', $atorId, 'resgate.entregar', $observacao, $rastro);
        });
    }

    /** O municipio confirma o recebimento: o debito vira definitivo, com consumo FIFO. */
    public function confirmar(int $pedidoId, string $observacao, int $atorId, Rastro $rastro): void
    {
        $this->emTransacao(function (Connection $db) use ($pedidoId, $observacao, $atorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['entregue']);
            $entregador = $db->selectOne("SELECT 1 FROM resgate.pedido_eventos WHERE pedido_id = ? AND etapa = 'entregar' AND ator_user_id = ?", [$pedidoId, $atorId]);
            if ($entregador !== null) {
                throw new RegraDoResgate('Quem entregou não confirma o recebimento.', 'pedido');
            }

            $this->movimentos->debitar($db, $pedido, new DateTimeImmutable());
            if ($pedido->unidade_id !== null) {
                $db->update("UPDATE resgate.unidades SET estado = 'entregue' WHERE id = ?", [(int) $pedido->unidade_id]);
            }
            $this->trilha->mudarStatus($db, $pedidoId, 'concluido', ['concluido_em' => 'now()']);
            $this->trilha->registrar($db, $pedidoId, 'confirmar', 'entregue', 'concluido', $atorId, 'resgate.solicitar', $observacao, $rastro);
        });
    }

    /** @param list<UploadedFile> $anexos */
    public function contestar(int $pedidoId, string $motivo, array $anexos, int $atorId, Rastro $rastro): void
    {
        $this->emTransacao(function (Connection $db) use ($pedidoId, $motivo, $anexos, $atorId, $rastro): void {
            $this->trilha->travar($db, $pedidoId, ['entregue']);
            foreach ($anexos as $arquivo) {
                $this->documentos->anexar($db, $pedidoId, 'contestacao', $arquivo, $atorId, $rastro);
            }
            $this->trilha->mudarStatus($db, $pedidoId, 'contestado');
            $this->trilha->registrar($db, $pedidoId, 'contestar', 'entregue', 'contestado', $atorId, 'resgate.solicitar', $motivo, $rastro);
        });
    }

    private function exigirOutraPessoa(object $pedido, int $atorId, string $mensagem): void
    {
        if ((int) $pedido->solicitado_por === $atorId) {
            throw new RegraDoResgate($mensagem, 'pedido');
        }
    }

    private function emTransacao(callable $passo): void
    {
        DB::connection((string) config('resgate.conexao'))->transaction($passo);
    }
}
