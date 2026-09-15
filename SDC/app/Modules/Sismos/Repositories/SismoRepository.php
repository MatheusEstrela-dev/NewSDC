<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Repositories;

use App\Modules\Sismos\DTOs\SismoDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SismoRepository
{
    private const CHUNK = 500;

    /**
     * Le a camada Gold para o mapa. Nenhuma agregacao acontece aqui: a matview
     * ja entrega lat/lon extraidos e a classe de magnitude calculada.
     *
     * @param array{min_lat: float, max_lat: float, min_lon: float, max_lon: float}|null $bbox
     */
    public function mapa(?array $bbox = null): Collection
    {
        $query = DB::table('gold.sismos_mapa')
            ->select([
                'id', 'fonte', 'evento_id', 'origem_utc', 'latitude', 'longitude',
                'magnitude', 'escala_magnitude', 'classe_magnitude',
                'profundidade_km', 'regiao',
            ])
            ->orderByDesc('origem_utc');

        if ($bbox !== null) {
            $query->whereRaw(
                'ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
                [$bbox['min_lon'], $bbox['min_lat'], $bbox['max_lon'], $bbox['max_lat']]
            );
        }

        return $query->get();
    }

    /**
     * @return array{total_eventos: int, magnitude_media: float, magnitude_maxima: float, ultimo_evento: ?string}
     */
    public function estatisticas(): array
    {
        $linha = DB::table('gold.sismos_estatisticas')->first();

        return [
            'total_eventos' => (int) ($linha->total_eventos ?? 0),
            'magnitude_media' => (float) ($linha->magnitude_media ?? 0),
            'magnitude_maxima' => (float) ($linha->magnitude_maxima ?? 0),
            // Data do evento mais recente, e nao o 'ultima_atualizacao' da
            // matview -- aquele campo e now() no momento do REFRESH, que so
            // acontece quando ha conteudo novo. Como o dedup por hash recusa
            // payload identico, ele congelava e a tela dizia "atualizado ontem"
            // enquanto o coletor rodava a cada 15 minutos.
            'ultimo_evento' => $this->ultimoEvento(),
        ];
    }

    /** Origem do evento mais recente na janela do mapa. */
    private function ultimoEvento(): ?string
    {
        return DB::scalar('SELECT max(origem_utc) FROM gold.sismos_mapa');
    }

    /**
     * Grava o lote na camada Silver, atualizando o que ja existe.
     *
     * @param iterable<SismoDTO> $dtos
     * @return int Quantidade de registros gravados
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $total = 0;
        $lote = [];

        foreach ($dtos as $dto) {
            $lote[] = $dto;

            if (count($lote) >= self::CHUNK) {
                $total += $this->gravar($lote, $ingestaoId);
                $lote = [];
            }
        }

        if ($lote !== []) {
            $total += $this->gravar($lote, $ingestaoId);
        }

        return $total;
    }

    public function totalPorFonte(string $fonte): int
    {
        return (int) DB::scalar('SELECT count(*) FROM silver.sismos WHERE fonte = ?', [$fonte]);
    }

    /**
     * SQL cru porque a coluna geom exige ST_SetSRID(ST_MakePoint(...)), que o
     * upsert() do Eloquent nao expressa.
     *
     * @param list<SismoDTO> $lote
     */
    private function gravar(array $lote, ?int $ingestaoId): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?, ?, ?, ?, ?, ?, now(), now())';

            array_push(
                $bindings,
                $dto->fonte,
                $dto->evento_id,
                $dto->origem_utc->toIso8601String(),
                $dto->longitude,   // PostGIS espera X (longitude) antes de Y (latitude)
                $dto->latitude,
                $dto->profundidade_km,
                $dto->magnitude,
                $dto->escala_magnitude,
                $dto->modo,
                $dto->regiao,
                $dto->tipo_evento,
                $dto->autor,
                $ingestaoId,
            );
        }

        $sql = 'INSERT INTO silver.sismos
                    (fonte, evento_id, origem_utc, geom, profundidade_km, magnitude,
                     escala_magnitude, modo, regiao, tipo_evento, autor, ingestao_id,
                     created_at, updated_at)
                VALUES ' . implode(', ', $placeholders) . '
                ON CONFLICT (fonte, evento_id) DO UPDATE SET
                    origem_utc       = EXCLUDED.origem_utc,
                    geom             = EXCLUDED.geom,
                    profundidade_km  = EXCLUDED.profundidade_km,
                    magnitude        = EXCLUDED.magnitude,
                    escala_magnitude = EXCLUDED.escala_magnitude,
                    modo             = EXCLUDED.modo,
                    regiao           = EXCLUDED.regiao,
                    tipo_evento      = EXCLUDED.tipo_evento,
                    autor            = EXCLUDED.autor,
                    ingestao_id      = EXCLUDED.ingestao_id,
                    updated_at       = now()';

        DB::statement($sql, $bindings);

        return count($lote);
    }
}
