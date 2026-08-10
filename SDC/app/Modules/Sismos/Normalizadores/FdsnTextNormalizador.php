<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Normalizadores;

use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Sismos\DTOs\SismoDTO;
use Carbon\CarbonImmutable;
use Generator;
use Throwable;

/**
 * Le o formato "text" do FDSN Event Web Service: 14 colunas separadas por "|",
 * cabecalho iniciado por "#".
 *
 * EventID|Time|Latitude|Longitude|Depth/km|Author|Catalog|Contributor|
 * ContributorID|MagType|Magnitude|MagAuthor|EventLocationName|EventType
 */
final class FdsnTextNormalizador implements NormalizadorSilver
{
    private const COLUNAS = 14;

    private const COL_EVENT_ID = 0;
    private const COL_TIME = 1;
    private const COL_LAT = 2;
    private const COL_LON = 3;
    private const COL_DEPTH = 4;
    private const COL_AUTHOR = 5;
    private const COL_MAG_TYPE = 9;
    private const COL_MAG = 10;
    private const COL_LOCATION = 12;
    private const COL_EVENT_TYPE = 13;

    /** @return Generator<SismoDTO> */
    public function normalizar(PayloadBruto $bruto): Generator
    {
        foreach (preg_split('/\R/', $bruto->conteudo) ?: [] as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            $campos = explode('|', $linha);

            if (count($campos) < self::COLUNAS) {
                continue;
            }

            $dto = $this->montar($campos);

            if ($dto !== null) {
                yield $dto;
            }
        }
    }

    /** @param list<string> $campos */
    private function montar(array $campos): ?SismoDTO
    {
        try {
            return new SismoDTO(
                fonte: 'usp-fdsn',
                evento_id: trim($campos[self::COL_EVENT_ID]),
                origem_utc: CarbonImmutable::parse(trim($campos[self::COL_TIME]), 'UTC'),
                latitude: (float) trim($campos[self::COL_LAT]),
                longitude: (float) trim($campos[self::COL_LON]),
                profundidade_km: $this->numero($campos[self::COL_DEPTH]),
                magnitude: $this->numero($campos[self::COL_MAG]),
                escala_magnitude: $this->texto($campos[self::COL_MAG_TYPE]),
                modo: null,
                regiao: $this->texto($campos[self::COL_LOCATION]),
                tipo_evento: $this->texto($campos[self::COL_EVENT_TYPE]),
                autor: $this->texto($campos[self::COL_AUTHOR]),
            );
        } catch (Throwable) {
            // Linha malformada nao derruba a coleta inteira. O Bronze preserva o
            // bruto, entao o registro perdido pode ser reprocessado depois.
            return null;
        }
    }

    private function numero(string $valor): ?float
    {
        $valor = trim($valor);

        return $valor === '' ? null : (float) $valor;
    }

    private function texto(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }
}
