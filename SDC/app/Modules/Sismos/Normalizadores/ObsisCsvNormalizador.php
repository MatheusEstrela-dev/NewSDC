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
 * Le o CSV do portal do obsis/UnB.
 *
 * Colunas: N, Data, Hora(UTC), Latitude, Longitude, Magnitude, Escala,
 * Profundidade(km), Local, Tipo, IDSCP3, Revisor.
 *
 * Duas particularidades da fonte:
 *
 * 1. O campo Local pode conter virgula (ex.: "Salvador, BA"), o que desalinha um
 *    split ingenuo. As 8 primeiras e as 3 ultimas posicoes sao fixas, e o miolo
 *    e rejuntado — mesma tratativa do ",".join(p[8:-3]) do script Python.
 *
 * 2. O campo Local traz o pais ("Brazil"), nao o estado. Filtrar MG por texto nao
 *    funciona: o recorte tem de ser geografico, e o portal nao aceita filtro no
 *    servidor, entao ele acontece aqui.
 */
final class ObsisCsvNormalizador implements NormalizadorSilver
{
    private const MIN_CAMPOS = 12;

    /** @return Generator<SismoDTO> */
    public function normalizar(PayloadBruto $bruto): Generator
    {
        $bbox = config('medalhao.sismos.bbox');

        foreach (preg_split('/\R/', $bruto->conteudo) ?: [] as $linha) {
            $linha = trim($linha);

            // Linha de dado sempre comeca com o numero sequencial; titulo e
            // cabecalho nao passam por aqui.
            if ($linha === '' || preg_match('/^\d+\s*,/', $linha) !== 1) {
                continue;
            }

            $campos = array_map('trim', explode(',', $linha));

            if (count($campos) < self::MIN_CAMPOS) {
                continue;
            }

            $dto = $this->montar($campos);

            if ($dto !== null && $dto->dentroDaBbox($bbox)) {
                yield $dto;
            }
        }
    }

    /** @param list<string> $campos */
    private function montar(array $campos): ?SismoDTO
    {
        $total = count($campos);
        $local = trim(implode(', ', array_slice($campos, 8, $total - 11)));

        try {
            $origem = CarbonImmutable::createFromFormat(
                'd/m/Y H:i:s',
                "{$campos[1]} {$campos[2]}",
                'UTC'
            );

            if (! $origem instanceof CarbonImmutable) {
                return null;
            }

            return new SismoDTO(
                fonte: 'unb-obsis',
                evento_id: $campos[$total - 2],
                origem_utc: $origem,
                latitude: (float) $campos[3],
                longitude: (float) $campos[4],
                profundidade_km: $this->numero($campos[7]),
                magnitude: $this->numero($campos[5]),
                escala_magnitude: $this->texto($campos[6]),
                modo: null,
                regiao: $local === '' ? null : $local,
                tipo_evento: $this->texto($campos[$total - 3]),
                autor: $this->texto($campos[$total - 1]),
            );
        } catch (Throwable) {
            // Data invalida ou linha corrompida nao derruba a coleta; o Bronze
            // preserva o bruto para reprocessamento.
            return null;
        }
    }

    private function numero(string $valor): ?float
    {
        return $valor === '' ? null : (float) $valor;
    }

    private function texto(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }
}
