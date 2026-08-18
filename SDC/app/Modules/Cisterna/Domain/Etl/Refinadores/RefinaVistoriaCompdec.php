<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefinaVistoriaCompdec implements Refinador
{
    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_compdec';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $crea = trim((string) ($doc['crea_mg'] ?? ''));
        $data = trim((string) ($doc['data_relatorio'] ?? ''));

        // O legado criava RelatorioInstalacaoCompdec::create(['instalacao_id'])
        // vazio junto com o store do fornecedor
        // (CisternaController.php:1682). Linha sem engenheiro e sem data e
        // placeholder, nao conferencia realizada.
        if ($crea === '' && ($data === '' || str_starts_with($data, '0000'))) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Linha vazia criada como efeito colateral no legado: sem conferencia registrada.');

            return;
        }

        $instalacaoLegacyId = (int) ($doc['instalacao_id'] ?? 0);

        // instalacao_id aponta para a vistoria do FORNECEDOR, nao para a
        // cisterna. Precisa dela para achar o beneficiario.
        $doFornecedor = CisternaVistoria::where('etapa', EtapaVistoria::FORNECEDOR->value)
            ->where('legacy_id', $instalacaoLegacyId)
            ->first(['id', 'beneficiario_id']);

        if ($doFornecedor === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Vistoria de fornecedor {$instalacaoLegacyId} nao encontrada. "
                .'Refinar sinc_cisterna_rel_fornecedor antes.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'dry-run: criaria conferencia da COMPDEC para o beneficiario '
                .$doFornecedor->beneficiario_id.'.');

            return;
        }

        $atributos = [
            'beneficiario_id' => (int) $doFornecedor->beneficiario_id,
            'etapa' => EtapaVistoria::COMPDEC->value,
            // Somente a etapa do fornecedor tem numero de instalacao.
            'numero_instalacao' => null,
            'engenheiro_crea' => $crea === '' ? null : mb_substr($crea, 0, 30),
            'data_relatorio' => $this->data($data),
            'local_relatorio' => $this->texto($doc['local_relatorio'] ?? null, 255),
            'concluida_em' => $crea === '' ? null : ($this->data($data) ?? now()->toDateTimeString()),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::COMPDEC->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                $vistoria = $existente ?? CisternaVistoria::create($atributos);

                if ($existente !== null) {
                    $existente->update($atributos);
                }

                $vistoria->itensConferidos()->delete();

                foreach (MapaItensLegado::paraEtapa(EtapaVistoria::COMPDEC, $doc) as $linha) {
                    $vistoria->itensConferidos()->create($linha);
                }

                $existente === null
                    ? RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id)
                    : RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar conferencia da COMPDEC: '.$e->getMessage(), $doc);
        }
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
