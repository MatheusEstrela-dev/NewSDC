<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Repositories;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use Illuminate\Support\Facades\DB;

final class InmetRepository
{
    private const CHUNK = 500;

    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * Aceita os dois tipos de DTO no mesmo iteravel porque uma coleta traz
     * inventario e leituras juntos, e o kernel chama o persistidor uma vez.
     *
     * @param iterable<EstacaoDTO|LeituraMeteorologicaDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $estacoes = [];
        $leituras = [];
        $total = 0;

        foreach ($dtos as $dto) {
            if ($dto instanceof EstacaoDTO) {
                $estacoes[] = $dto;
            } else {
                $leituras[] = $dto;
            }

            if (count($estacoes) >= self::CHUNK) {
                $total += $this->gravarEstacoes($estacoes);
                $estacoes = [];
            }

            if (count($leituras) >= self::CHUNK) {
                $total += $this->gravarLeituras($leituras, $ingestaoId);
                $leituras = [];
            }
        }

        if ($estacoes !== []) {
            $total += $this->gravarEstacoes($estacoes);
        }

        if ($leituras !== []) {
            $total += $this->gravarLeituras($leituras, $ingestaoId);
        }

        return $total;
    }

    public function totalLeituras(): int
    {
        return (int) DB::scalar('SELECT count(*) FROM silver.leituras_inmet');
    }

    /**
     * SQL cru porque geom exige ST_SetSRID(ST_MakePoint(...)), que o upsert()
     * do Eloquent nao expressa.
     *
     * @param list<EstacaoDTO> $lote
     */
    private function gravarEstacoes(array $lote): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), now(), now())';

            array_push(
                $bindings,
                $dto->codigo,
                $dto->nome,
                $this->municipioMaisProximo($dto->latitude, $dto->longitude, $dto->uf) ?? $dto->nome,
                $dto->uf,
                $dto->latitude,
                $dto->longitude,
                $dto->altitude,
                $dto->tipo ?? 'automatica',
                $dto->situacao,
                $dto->longitude,     // PostGIS espera X (longitude) antes de Y
                $dto->latitude,
            );
        }

        DB::statement(
            'INSERT INTO estacoes_meteorologicas
                (codigo, nome, municipio, uf, latitude, longitude, altitude, tipo, situacao, geom, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo) DO UPDATE SET
                nome       = EXCLUDED.nome,
                municipio  = EXCLUDED.municipio,
                uf         = EXCLUDED.uf,
                latitude   = EXCLUDED.latitude,
                longitude  = EXCLUDED.longitude,
                altitude   = EXCLUDED.altitude,
                tipo       = EXCLUDED.tipo,
                situacao   = EXCLUDED.situacao,
                geom       = EXCLUDED.geom,
                updated_at = now()',
            $bindings
        );

        return count($lote);
    }

    /**
     * O inventario do INMET nao traz municipio: traz DC_NOME, que e nome de
     * estacao ("BELO HORIZONTE - PAMPULHA"), e SG_ESTADO. Como a coluna
     * municipio e NOT NULL, resolve-se pelo centroide mais proximo entre os 853
     * municipios de MG ja semeados.
     *
     * ATENCAO: e centroide, nao contencao por poligono — a tabela municipios
     * tem latitude/longitude, nao geometria de area. Estacao perto de divisa
     * pode resolver para o municipio vizinho. Verificado para A521: resolve
     * Belo Horizonte a 5,3 km, contra Contagem a 10,3 km.
     */
    private function municipioMaisProximo(float $latitude, float $longitude, string $uf): ?string
    {
        return DB::scalar(
            'SELECT m.nome
               FROM municipios m
              WHERE m.uf = ?
                AND m.latitude IS NOT NULL
                AND m.longitude IS NOT NULL
              ORDER BY ST_Distance(
                        ST_SetSRID(ST_MakePoint(m.longitude::float8, m.latitude::float8), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                      ) ASC
              LIMIT 1',
            [$uf, $longitude, $latitude]
        );
    }

    /** @param list<LeituraMeteorologicaDTO> $lote */
    private function gravarLeituras(array $lote, ?int $ingestaoId): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, now(), now())';

            array_push(
                $bindings,
                $dto->codigoEstacao,
                $dto->dataHoraMedicao->toIso8601String(),
                $dto->temperatura,
                $dto->umidade,
                $dto->precipitacao,
                $dto->velocidadeVento,
                $dto->pressao,
                $ingestaoId,
            );
        }

        DB::statement(
            'INSERT INTO silver.leituras_inmet
                (codigo_estacao, medido_em, temperatura, umidade, precipitacao,
                 velocidade_vento, pressao, ingestao_id, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo_estacao, medido_em) DO UPDATE SET
                temperatura      = EXCLUDED.temperatura,
                umidade          = EXCLUDED.umidade,
                precipitacao     = EXCLUDED.precipitacao,
                velocidade_vento = EXCLUDED.velocidade_vento,
                pressao          = EXCLUDED.pressao,
                ingestao_id      = EXCLUDED.ingestao_id,
                updated_at       = now()',
            $bindings
        );

        return count($lote);
    }
}
