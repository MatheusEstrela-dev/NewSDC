<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Registry;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use InvalidArgumentException;

/**
 * Mantem o par (ingestor, normalizador) por chave de fonte.
 *
 * Os modulos de dominio registram as proprias fontes no boot do seu provider,
 * o que mantem o kernel do medalhao sem conhecer dominio nenhum.
 */
final class IngestorRegistry
{
    /** @var array<string, FonteIngestor> */
    private array $ingestores = [];

    /** @var array<string, NormalizadorSilver> */
    private array $normalizadores = [];

    public function registrar(FonteIngestor $ingestor, NormalizadorSilver $normalizador): void
    {
        $this->ingestores[$ingestor->chave()] = $ingestor;
        $this->normalizadores[$ingestor->chave()] = $normalizador;
    }

    public function ingestor(string $chave): FonteIngestor
    {
        return $this->ingestores[$chave]
            ?? throw new InvalidArgumentException("Fonte nao registrada: {$chave}");
    }

    public function normalizador(string $chave): NormalizadorSilver
    {
        return $this->normalizadores[$chave]
            ?? throw new InvalidArgumentException("Normalizador nao registrado: {$chave}");
    }

    /** @return list<string> */
    public function chavesDoGrupo(string $grupo): array
    {
        $chaves = [];

        foreach ($this->ingestores as $chave => $ingestor) {
            if ($ingestor->grupo() === $grupo) {
                $chaves[] = $chave;
            }
        }

        return $chaves;
    }
}
