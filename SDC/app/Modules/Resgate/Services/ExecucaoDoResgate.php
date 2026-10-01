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
 *   APROVADO       --termo (documento SEI do termo + PDF)-->        TERMO_EMITIDO
 *   TERMO_EMITIDO  --assinar pelo Estado E pelo municipio-->         TERMO_ASSINADO
 *   TERMO_ASSINADO --entregar (unidade responsavel + evidencias)-->  ENTREGUE
 *   ENTREGUE       --confirmar (municipio)-->                        CONCLUIDO (debito definitivo)
 *   ENTREGUE       --contestar (municipio)-->                        CONTESTADO --entregar--> ENTREGUE
 *   qualquer etapa antes de CONCLUIDO --anular (CEDEC + documento)--> ANULADO (libera)
 *
 * O processo SEI vem da aprovacao; o SDC nao assina: o termo e assinado no
 * SEI e cada parte registra aqui o numero do documento. Cada etapa passa pela
 * segregacao de papeis (TrilhaDoPedido) e respeita bloqueio vigente (P7).
 * O debito so vira definitivo na CONFIRMACAO do municipio.
 */
final class ExecucaoDoResgate
{
    private const ANULAVEIS = ['aprovado', 'termo_emitido', 'termo_assinado', 'entregue', 'contestado'];

    public function __construct(
        private readonly TrilhaDoPedido $trilha,
        private readonly MovimentosDaCarteira $movimentos,
        private readonly DocumentosDoPedido $documentos,
        private readonly BloqueiosDoResgate $bloqueios,
    ) {}

    public function emitirTermo(int $pedidoId, string $documentoSei, UploadedFile $termo, int $atorId, Rastro $rastro): void
    {
        $this->passo($pedidoId, ['aprovado'], 'termo', $atorId, function (Connection $db, object $pedido) use ($pedidoId, $documentoSei, $termo, $atorId, $rastro): void {
            $this->documentos->anexar($db, $pedidoId, 'termo', $termo, $atorId, $rastro);
            $this->trilha->mudarStatus($db, $pedidoId, 'termo_emitido', ['termo_documento_sei' => $documentoSei]);
            $this->trilha->registrar($db, $pedidoId, 'termo', 'aprovado', 'termo_emitido', $atorId, 'resgate.aprovar',
                "Processo SEI {$pedido->processo_sei}; termo documento SEI {$documentoSei}.", $rastro);
        });
    }

    /** @param 'estado'|'municipio' $parte */
    public function assinar(int $pedidoId, string $parte, string $documentoSei, int $atorId, Rastro $rastro): void
    {
        $etapa = "assinar_{$parte}";
        $this->passo($pedidoId, ['termo_emitido'], $etapa, $atorId, function (Connection $db, object $pedido) use ($pedidoId, $parte, $etapa, $documentoSei, $atorId, $rastro): void {
            $coluna = $parte === 'estado' ? 'assinado_estado' : 'assinado_municipio';
            if ($pedido->{"{$coluna}_por"} !== null) {
                throw new RegraDoResgate('Esta parte já registrou a assinatura.', 'pedido');
            }
            $outraAssinou = $parte === 'estado' ? $pedido->assinado_municipio_por !== null : $pedido->assinado_estado_por !== null;
            $novo = $outraAssinou ? 'termo_assinado' : 'termo_emitido';

            $this->trilha->mudarStatus($db, $pedidoId, $novo, ["{$coluna}_por" => $atorId, "{$coluna}_em" => 'now()']);
            $this->trilha->registrar($db, $pedidoId, $etapa, 'termo_emitido', $novo, $atorId,
                $parte === 'estado' ? 'resgate.aprovar' : 'resgate.solicitar',
                "Assinatura registrada no SEI, documento {$documentoSei}.", $rastro);
        });
    }

    /** @param list<UploadedFile> $evidencias */
    public function entregar(int $pedidoId, array $evidencias, string $observacao, int $atorId, Rastro $rastro): void
    {
        if ($evidencias === []) {
            throw new RegraDoResgate('A entrega exige ao menos uma evidência (termo de recebimento, foto).', 'evidencias');
        }

        $this->passo($pedidoId, ['termo_assinado', 'contestado'], 'entregar', $atorId, function (Connection $db, object $pedido) use ($pedidoId, $evidencias, $observacao, $atorId, $rastro): void {
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
        $this->passo($pedidoId, ['entregue'], 'confirmar', $atorId, function (Connection $db, object $pedido) use ($pedidoId, $observacao, $atorId, $rastro): void {
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
        $this->passo($pedidoId, ['entregue'], 'contestar', $atorId, function (Connection $db) use ($pedidoId, $motivo, $anexos, $atorId, $rastro): void {
            foreach ($anexos as $arquivo) {
                $this->documentos->anexar($db, $pedidoId, 'contestacao', $arquivo, $atorId, $rastro);
            }
            $this->trilha->mudarStatus($db, $pedidoId, 'contestado');
            $this->trilha->registrar($db, $pedidoId, 'contestar', 'entregue', 'contestado', $atorId, 'resgate.solicitar', $motivo, $rastro);
        });
    }

    /**
     * Saida administrativa ANTES da conclusao (decisao administrativa ou
     * judicial, com o documento de origem): libera pontos e unidade. Passa por
     * cima do bloqueio de proposito - cumprir a decisao que originou o bloqueio
     * e justamente o caso de uso. Depois de concluido, a devolucao e processo
     * proprio, fora deste fluxo.
     */
    public function anular(int $pedidoId, string $documentoOrigem, string $motivo, int $atorId, Rastro $rastro): void
    {
        DB::connection((string) config('resgate.conexao'))->transaction(function (Connection $db) use ($pedidoId, $documentoOrigem, $motivo, $atorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, self::ANULAVEIS);
            $this->trilha->exigirPapelLivre($db, $pedidoId, 'anular', $atorId);
            $this->movimentos->liberar($db, $pedido);
            $this->trilha->mudarStatus($db, $pedidoId, 'anulado');
            $this->trilha->registrar($db, $pedidoId, 'anular', (string) $pedido->status, 'anulado', $atorId, 'resgate.aprovar',
                "Anulado com base em {$documentoOrigem}: {$motivo}", $rastro);
        });
    }

    /**
     * Esqueleto comum das etapas: trava o pedido no status esperado, confere o
     * papel (segregacao) e o bloqueio ANTES de qualquer efeito, e executa.
     *
     * @param list<string> $status
     */
    private function passo(int $pedidoId, array $status, string $etapa, int $atorId, callable $efeito): void
    {
        DB::connection((string) config('resgate.conexao'))->transaction(function (Connection $db) use ($pedidoId, $status, $etapa, $atorId, $efeito): void {
            $pedido = $this->trilha->travar($db, $pedidoId, $status);
            $this->trilha->exigirPapelLivre($db, $pedidoId, $etapa, $atorId);
            $this->bloqueios->exigirLiberado($db, $pedido);
            $efeito($db, $pedido);
        });
    }
}
