<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aplica a moderacao geoespacial a bases que JA rodaram a migration de
     * origem.
     *
     * Por que esta migration existe, contrariando a regra 9 do projeto:
     *
     * As colunas de procedencia e o filtro de status foram escritos em
     * 2026_09_03_000003 e 2026_09_03_000004, que ja estavam aplicadas. Aqueles
     * arquivos usam CREATE TABLE IF NOT EXISTS e CREATE MATERIALIZED VIEW IF
     * NOT EXISTS, entao numa base existente eles NAO FAZEM NADA.
     *
     * Para o caso do verificado_em, mais cedo, aceitou-se o efeito colateral: a
     * consequencia era coluna faltando, e coluna faltando estoura alto no
     * primeiro INSERT. Aqui a consequencia e diferente e pior:
     * gold.geo_feicao_mapa manteria a definicao SEM `WHERE c.status =
     * 'aprovada'`, e a moderacao inteira deixaria de existir -- geometria
     * municipal pendente seria publicada no mapa operacional, em silencio,
     * sem ninguem perceber que a aprovacao nao esta sendo respeitada.
     *
     * Falha silenciosa que degrada seguranca nao e equivalente a erro visivel.
     * Por isso aqui vai migration de verdade, e nao DDL aplicado a mao.
     *
     * Idempotente de ponta a ponta: rodar em base nova, que ja nasceu correta
     * pela migration de origem, nao muda nada.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE silver.geo_camadas
              ADD COLUMN IF NOT EXISTS origem          varchar(12)  NOT NULL DEFAULT 'estadual',
              ADD COLUMN IF NOT EXISTS municipio_id    bigint       NULL,
              ADD COLUMN IF NOT EXISTS orgao_id        bigint       NULL,
              ADD COLUMN IF NOT EXISTS enviado_por     bigint       NULL,
              ADD COLUMN IF NOT EXISTS status          varchar(12)  NOT NULL DEFAULT 'aprovada',
              ADD COLUMN IF NOT EXISTS revisado_por    bigint       NULL,
              ADD COLUMN IF NOT EXISTS revisado_em     timestamptz  NULL,
              ADD COLUMN IF NOT EXISTS motivo_recusa   text         NULL,
              ADD COLUMN IF NOT EXISTS arquivo_caminho varchar(500) NULL
        SQL);

        // As FK e os CHECK entram por bloco condicional porque ADD CONSTRAINT
        // nao aceita IF NOT EXISTS no Postgres.
        //
        // municipio_id e RESTRICT e nao SET NULL: com SET NULL, apagar um
        // municipio que tem camada municipal zeraria a coluna e a linha
        // passaria a violar ck_silver_geo_camadas_municipal -- o DELETE
        // abortaria com erro de check, incompreensivel para quem so quis
        // remover um municipio.
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'geo_camadas_municipio_id_foreign') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT geo_camadas_municipio_id_foreign
                  FOREIGN KEY (municipio_id) REFERENCES municipios (id) ON DELETE RESTRICT;
              END IF;

              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'geo_camadas_enviado_por_foreign') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT geo_camadas_enviado_por_foreign
                  FOREIGN KEY (enviado_por) REFERENCES users (id) ON DELETE SET NULL;
              END IF;

              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'geo_camadas_revisado_por_foreign') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT geo_camadas_revisado_por_foreign
                  FOREIGN KEY (revisado_por) REFERENCES users (id) ON DELETE SET NULL;
              END IF;

              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_silver_geo_camadas_origem') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT ck_silver_geo_camadas_origem
                  CHECK (origem IN ('estadual', 'municipal'));
              END IF;

              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_silver_geo_camadas_status') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT ck_silver_geo_camadas_status
                  CHECK (status IN ('pendente', 'aprovada', 'recusada'));
              END IF;

              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_silver_geo_camadas_municipal') THEN
                ALTER TABLE silver.geo_camadas ADD CONSTRAINT ck_silver_geo_camadas_municipal
                  CHECK (origem <> 'municipal' OR municipio_id IS NOT NULL);
              END IF;
            END $$
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_status ON silver.geo_camadas (origem, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_municipio ON silver.geo_camadas (municipio_id, status)');

        // O ponto critico: sem recriar, a matview publica pendente.
        // geo_camada_municipios cai primeiro so por simetria -- ela le o
        // Silver direto e nao depende de geo_feicao_mapa.
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_camada_municipios');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_feicao_mapa');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW gold.geo_feicao_mapa AS
            SELECT
                f.id,
                f.camada_id,
                c.dominio,
                c.nome        AS camada_nome,
                c.nivel,
                c.emitido_em,
                c.origem,
                c.municipio_id,
                m.nome        AS municipio_nome,
                f.nome        AS feicao_nome,
                f.propriedades,
                ST_GeometryType(f.geom)                                  AS tipo_geometria,
                round((ST_Area(f.geom::geography) / 1000000)::numeric, 2) AS area_km2,
                ST_AsGeoJSON(f.geom)::jsonb                              AS geojson,
                f.geom
            FROM silver.geo_feicoes f
            JOIN silver.geo_camadas c ON c.id = f.camada_id
            LEFT JOIN municipios m ON m.id = c.municipio_id
            WHERE c.status = 'aprovada'
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_feicao_mapa_id ON gold.geo_feicao_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_geom ON gold.geo_feicao_mapa USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_camada ON gold.geo_feicao_mapa (camada_id)');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW gold.geo_camada_municipios AS
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
              AND c.status = 'aprovada'
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

        // Nao remove as colunas: o down existe para desfazer a MODERACAO, e
        // apagar procedencia levaria embora quem enviou o que. Camada municipal
        // ja recebida perderia a autoria sem chance de recuperar.
        DB::statement("UPDATE silver.geo_camadas SET status = 'aprovada' WHERE status <> 'aprovada'");
    }
};
