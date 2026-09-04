<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // GeoJSON pronto pelo banco: o request de leitura nao serializa
        // poligono, so le linha feita. Mesma disciplina do gold.inmet_mapa,
        // que ja entrega lat/lon extraidos da geometria.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_feicao_mapa AS
            SELECT
                f.id,
                f.camada_id,
                c.dominio,
                c.nome        AS camada_nome,
                c.nivel,
                c.emitido_em,
                f.nome        AS feicao_nome,
                f.propriedades,
                ST_GeometryType(f.geom)                     AS tipo_geometria,
                round((ST_Area(f.geom::geography) / 1000000)::numeric, 2) AS area_km2,
                ST_AsGeoJSON(f.geom)::jsonb                 AS geojson,
                f.geom
            FROM silver.geo_feicoes f
            JOIN silver.geo_camadas c ON c.id = f.camada_id
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_feicao_mapa_id ON gold.geo_feicao_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_geom ON gold.geo_feicao_mapa USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_camada ON gold.geo_feicao_mapa (camada_id)');

        // Cruzamento com municipios. ATENCAO: e centroide dentro do poligono, e
        // nao intersecao de area -- a tabela municipios guarda
        // latitude/longitude, nao geometria de territorio. Municipio cujo
        // centroide cai fora mas cujo territorio e atingido NAO entra. O numero
        // e piso, nao total, e a tela precisa dizer isso.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_camada_municipios AS
            SELECT
                row_number() OVER (ORDER BY c.id, m.nome) AS id,
                c.id   AS camada_id,
                m.id   AS municipio_id,
                m.nome AS municipio_nome,
                m.uf
            FROM silver.geo_camadas c
            JOIN silver.geo_feicoes f ON f.camada_id = c.id
            JOIN municipios m
              ON ST_Contains(f.geom, ST_SetSRID(ST_MakePoint(m.longitude::float8, m.latitude::float8), 4326))
            WHERE m.latitude IS NOT NULL
              AND m.longitude IS NOT NULL
            GROUP BY c.id, m.id, m.nome, m.uf
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_camada_municipios ON gold.geo_camada_municipios (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_camada_municipios_camada ON gold.geo_camada_municipios (camada_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_camada_municipios');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_feicao_mapa');
    }
};
