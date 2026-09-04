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

        // Cabecalho da camada: um registro por arquivo enviado. O dominio e
        // coluna e nao tabela porque o que varia entre hidro, geologico e
        // meteorologico e legenda e vocabulario, nao estrutura.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_camadas (
                id           bigserial PRIMARY KEY,
                dominio      varchar(20)  NOT NULL,
                nome         varchar(255) NOT NULL,
                arquivo_nome varchar(255) NOT NULL,
                emitido_em   date         NULL,
                valido_ate   date         NULL,
                nivel        varchar(40)  NULL,
                hash_arquivo char(64)     NOT NULL,
                ingestao_id  bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_geo_camadas_hash UNIQUE (hash_arquivo)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_dominio ON silver.geo_camadas (dominio, emitido_em DESC)');

        // Uma linha por Placemark. geometry(Geometry,4326) e nao MultiPolygon:
        // verificado que um campo unico com um GIST serve poligono, linha e
        // ponto, e hidro traz rio como linha.
        //
        // propriedades jsonb porque ExtendedData varia por fonte. O arquivo de
        // 28/02 nao tem nenhum, mas aviso meteorologico carrega atributos, e sem
        // o jsonb cada fonte nova pediria migration.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_feicoes (
                id           bigserial PRIMARY KEY,
                camada_id    bigint       NOT NULL REFERENCES silver.geo_camadas (id) ON DELETE CASCADE,
                nome         varchar(255) NULL,
                propriedades jsonb        NOT NULL DEFAULT '{}'::jsonb,
                geom         geometry(Geometry, 4326) NOT NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_geom ON silver.geo_feicoes USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_camada ON silver.geo_feicoes (camada_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.geo_feicoes');
        DB::statement('DROP TABLE IF EXISTS silver.geo_camadas');
    }
};
