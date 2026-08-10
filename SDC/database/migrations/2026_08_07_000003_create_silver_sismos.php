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

        // Camada Silver: tipada e geografica. A geometria e coluna PostGIS de
        // verdade, nao par de decimais soltos — e o que permite indice espacial e
        // consulta por bounding box no banco em vez de na aplicacao.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.sismos (
                id               bigserial PRIMARY KEY,
                fonte            varchar(64)  NOT NULL,
                evento_id        varchar(64)  NOT NULL,
                origem_utc       timestamptz  NOT NULL,
                geom             geometry(Point, 4326) NOT NULL,
                profundidade_km  numeric(8,3) NULL,
                magnitude        numeric(5,3) NULL,
                escala_magnitude varchar(16)  NULL,
                modo             varchar(16)  NULL,
                regiao           text         NULL,
                tipo_evento      varchar(32)  NULL,
                autor            varchar(64)  NULL,
                ingestao_id      bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at       timestamptz  NOT NULL DEFAULT now(),
                updated_at       timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_sismos_fonte_evento UNIQUE (fonte, evento_id)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_sismos_geom ON silver.sismos USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_sismos_origem ON silver.sismos (origem_utc DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.sismos');
    }
};
