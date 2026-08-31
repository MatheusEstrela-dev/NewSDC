<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;

class RefinaOrdensServico implements Refinador
{
    public function recurso(): string
    {
        return 'os';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_ordem_servico';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['nome'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Ordem de servico sem nome.', $doc);

            return;
        }

        $loteLegacyId = $doc['lote_id'] ?? null;
        $loteId = $loteLegacyId === null
            ? null
            : CisternaLote::where('legacy_id', (int) $loteLegacyId)->value('id');

        if ($loteId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Lote de origem {$loteLegacyId} nao encontrado. Refinar lotes antes.", $doc);

            return;
        }

        $atributos = [
            'lote_id' => (int) $loteId,
            'nome' => $nome,
            // Legado: coluna obs.
            'observacao' => $this->textoOuNulo($doc['obs'] ?? null),
            // link_doc e URL do SEI, nao arquivo: vai para coluna, nao para
            // MediaLibrary. O placeholder '-' do legado vira null.
            'documento_url' => $this->urlOuNulo($doc['link_doc'] ?? null),
            'legacy_id' => $legacyId,
        ];

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria OS \"{$nome}\" no lote {$loteId}.");

            return;
        }

        $existente = CisternaOrdemServico::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update($atributos);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        $criada = CisternaOrdemServico::create($atributos);
        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
    }

    private function textoOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    /**
     * 3 das 7 ordens do legado tem URL do SEI; as outras 4 tem o placeholder
     * '-'. Qualquer coisa que nao pareca URL vira null.
     */
    private function urlOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || $texto === '-' || ! str_starts_with($texto, 'http')) {
            return null;
        }

        return mb_substr($texto, 0, 500);
    }
}
