<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaLote;

class RefinaLotes implements Refinador
{
    public function recurso(): string
    {
        return 'lotes';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_lotes';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['nome'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Lote sem nome.', $doc);

            return;
        }

        $atributos = [
            'nome' => $nome,
            'data' => $this->dataOuNulo($doc['data'] ?? null),
            'legacy_id' => $legacyId,
        ];

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria lote \"{$nome}\".");

            return;
        }

        $existente = CisternaLote::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update($atributos);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        $criado = CisternaLote::create($atributos);
        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criado->id);
    }

    /**
     * O legado guardava data em varchar; valor invalido vira null em vez de
     * derrubar a carga.
     */
    private function dataOuNulo(mixed $valor): ?string
    {
        if ($valor === null || $valor === '' || $valor === '0000-00-00') {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::parse((string) $valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
