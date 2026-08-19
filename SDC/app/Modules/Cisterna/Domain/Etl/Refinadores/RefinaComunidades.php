<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\PonteMunicipio;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaComunidade;

class RefinaComunidades implements Refinador
{
    public function __construct(
        private readonly PonteMunicipio $ponte,
    ) {}

    public function recurso(): string
    {
        return 'comunidades';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_com';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['comunidade'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Comunidade sem nome.', $doc);

            return;
        }

        $municipioId = $this->ponte->resolver($doc['codmundv'] ?? null)
            ?? $this->ponte->resolverPorNome($doc['municipio'] ?? null);

        if ($municipioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Municipio sem correspondencia IBGE em municipios.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria comunidade \"{$nome}\" no municipio {$municipioId}.");

            return;
        }

        $existente = CisternaComunidade::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update(['municipio_id' => $municipioId, 'nome' => $nome]);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        // Comunidade ja cadastrada manualmente com o mesmo par
        // (municipio, nome): adota em vez de estourar o unique.
        $mesmoPar = CisternaComunidade::where('municipio_id', $municipioId)
            ->where('nome', $nome)
            ->first();

        if ($mesmoPar !== null) {
            $mesmoPar->update(['legacy_id' => $legacyId]);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $mesmoPar->id);

            return;
        }

        $criada = CisternaComunidade::create([
            'municipio_id' => $municipioId,
            'nome' => $nome,
            'ativa' => true,
            'legacy_id' => $legacyId,
        ]);

        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
    }
}
