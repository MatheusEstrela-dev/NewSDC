<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Repositories;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GeoCamadaRepository
{
    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * @param iterable<CamadaGeoDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $total = 0;

        foreach ($dtos as $dto) {
            $total += $this->gravarCamada($dto, $ingestaoId);
        }

        return $total;
    }

    private function gravarCamada(CamadaGeoDTO $dto, ?int $ingestaoId): int
    {
        // DO NOTHING e nao DO UPDATE: camada e imutavel. O mesmo arquivo
        // reenviado nao deve reescrever nem reimportar feicao -- e por isso que
        // hash_arquivo e unico.
        $id = DB::scalar(
            'INSERT INTO silver.geo_camadas
                (dominio, nome, arquivo_nome, emitido_em, valido_ate, nivel, hash_arquivo, ingestao_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), now())
             ON CONFLICT (hash_arquivo) DO NOTHING
             RETURNING id',
            [
                $dto->dominio, $dto->nome, $dto->arquivoNome,
                $dto->emitidoEm, $dto->validoAte, $dto->nivel,
                $dto->hashArquivo, $ingestaoId,
            ]
        );

        // null significa conflito: a camada ja existia e nada foi inserido.
        if ($id === null) {
            return 0;
        }

        foreach ($dto->feicoes as $feicao) {
            DB::statement(
                'INSERT INTO silver.geo_feicoes (camada_id, nome, propriedades, geom, created_at, updated_at)
                 VALUES (?, ?, ?::jsonb, ST_MakeValid(ST_Force2D(ST_GeomFromKML(?))), now(), now())',
                [(int) $id, $feicao->nome, '{}', $feicao->kmlGeometria]
            );
        }

        return count($dto->feicoes);
    }

    /**
     * Camada ja importada com esta geometria, ou null.
     *
     * Serve para o upload responder na hora em vez de aceitar, enfileirar e
     * deixar o ON CONFLICT recusar em silencio no worker.
     */
    public function camadaDoHash(string $hashArquivo): ?object
    {
        return DB::table('silver.geo_camadas')
            ->select(['id', 'nome', 'emitido_em'])
            ->where('hash_arquivo', $hashArquivo)
            ->first();
    }

    /** @return Collection<int, object> */
    public function camadas(): Collection
    {
        return DB::table('silver.geo_camadas')
            ->select(['id', 'dominio', 'nome', 'arquivo_nome', 'emitido_em', 'valido_ate', 'nivel', 'created_at'])
            ->orderByDesc('emitido_em')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Le a camada Gold. Nenhuma serializacao aqui: a matview ja entrega o
     * GeoJSON pronto.
     *
     * @return Collection<int, object>
     */
    public function mapa(?int $camadaId = null): Collection
    {
        $query = DB::table('gold.geo_feicao_mapa')
            ->select(['id', 'camada_id', 'dominio', 'camada_nome', 'nivel', 'emitido_em', 'feicao_nome', 'tipo_geometria', 'area_km2', 'geojson']);

        if ($camadaId !== null) {
            $query->where('camada_id', $camadaId);
        }

        return $query->orderByDesc('area_km2')->get();
    }

    /**
     * Cruzamento espacial da camada com o dado que o sistema ja tem.
     *
     * ATENCAO: 'municipios' vem de gold.geo_camada_municipios, que cruza por
     * CENTROIDE -- a tabela municipios nao tem geometria de area. O numero e
     * piso, nao total, e a tela precisa dizer isso.
     *
     * @return array{municipios: int, estacoes: int, chuva_media: float, chuva_maxima: float, estacoes_com_leitura: int}
     */
    public function cruzamento(int $camadaId): array
    {
        $municipios = (int) DB::scalar(
            'SELECT count(*) FROM gold.geo_camada_municipios WHERE camada_id = ?',
            [$camadaId]
        );

        // As DUAS redes, e nao so o CEMADEN: as 61 do INMET vivem em
        // estacoes_meteorologicas e ficavam de fora da conta. Hoje nenhuma cai
        // nas areas carregadas, entao o numero nao mudava -- mas isso era
        // coincidencia da geometria, nao correcao do codigo.
        //
        // ST_Intersects e nao ST_Contains: estacao exatamente sobre a divisa
        // entra. Errar incluindo e melhor que errar excluindo num alerta.
        $estacoes = (int) DB::scalar(
            'SELECT
                (SELECT count(*) FROM silver.estacoes_cemaden e
                  WHERE EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, e.geom)))
              + (SELECT count(*) FROM estacoes_meteorologicas m
                  WHERE m.geom IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, m.geom)))',
            [$camadaId, $camadaId]
        );

        // Chuva das duas redes numa serie so: as duas medem acumulado de 24h
        // com as mesmas faixas, entao separa-las na estatistica diria que
        // choveu duas coisas diferentes na mesma area.
        $chuva = DB::selectOne(
            'SELECT round(avg(mm)::numeric, 2) AS media,
                    max(mm)                    AS maxima,
                    count(*)                   AS com_leitura
               FROM (
                 SELECT g.acumulado_24h::float8 AS mm
                   FROM gold.cemaden_mapa g
                  WHERE g.acumulado_24h IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, g.geom))
                 UNION ALL
                 SELECT i.precipitacao::float8
                   FROM gold.inmet_mapa i
                  WHERE i.precipitacao IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, i.geom))
               ) AS leituras',
            [$camadaId, $camadaId]
        );

        return [
            'municipios' => $municipios,
            'estacoes' => $estacoes,
            'chuva_media' => (float) ($chuva->media ?? 0),
            'chuva_maxima' => (float) ($chuva->maxima ?? 0),
            'estacoes_com_leitura' => (int) ($chuva->com_leitura ?? 0),
        ];
    }
}
