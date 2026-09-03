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

    /** @var array<string, string> chave -> grupo, para fontes sem ingestor. */
    private array $gruposPush = [];

    public function registrar(FonteIngestor $ingestor, NormalizadorSilver $normalizador): void
    {
        $this->ingestores[$ingestor->chave()] = $ingestor;
        $this->normalizadores[$ingestor->chave()] = $normalizador;
    }

    /**
     * Registra uma fonte que NAO e coletada: o conteudo chega por upload.
     *
     * Existe porque FonteIngestor::coletar() e contrato de pull agendado, e
     * upload nao tem o que coletar. Forcar um ingestor com coletar() que nunca
     * e chamado seria mentir no contrato para satisfazer o registro.
     */
    public function registrarPush(string $chave, string $grupo, NormalizadorSilver $normalizador): void
    {
        $this->normalizadores[$chave] = $normalizador;
        $this->gruposPush[$chave] = $grupo;
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

        foreach ($this->gruposPush as $chave => $grupoPush) {
            if ($grupoPush === $grupo) {
                $chaves[] = $chave;
            }
        }

        return $chaves;
    }
}
