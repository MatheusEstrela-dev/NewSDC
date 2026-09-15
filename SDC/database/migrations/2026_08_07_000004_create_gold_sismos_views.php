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

        // A janela e interpolada na definicao da view: matview nao aceita
        // parametro em tempo de leitura. Mudar janela_mapa_dias exige recriar a
        // view (rollback e reaplicacao desta migration).
        $dias = (int) config('medalhao.sismos.janela_mapa_dias', 90);

        DB::statement(<<<SQL
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.sismos_mapa AS
            SELECT
                s.id,
                s.fonte,
                s.evento_id,
                s.origem_utc,
                ST_Y(s.geom) AS latitude,
                ST_X(s.geom) AS longitude,
                s.geom,
                s.magnitude,
                s.escala_magnitude,
                s.profundidade_km,
                s.regiao,
                CASE
                    WHEN s.magnitude IS NULL  THEN 'desconhecido'
                    WHEN s.magnitude <  2.0   THEN 'micro'
                    WHEN s.magnitude <  4.0   THEN 'leve'
                    WHEN s.magnitude <  5.0   THEN 'moderado'
                    ELSE 'forte'
                END AS classe_magnitude
            FROM silver.sismos s
            WHERE s.origem_utc >= now() - INTERVAL '{$dias} days'
        SQL);

        // Indice UNICO e obrigatorio para REFRESH ... CONCURRENTLY, e o
        // CONCURRENTLY e o que evita travar a leitura do mapa durante o refresh.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_sismos_mapa_id ON gold.sismos_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_sismos_mapa_geom ON gold.sismos_mapa USING GIST (geom)');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.sismos_estatisticas AS
            SELECT
                1                        AS id,
                count(*)                 AS total_eventos,
                round(avg(magnitude), 2) AS magnitude_media,
                max(magnitude)           AS magnitude_maxima,
                now()                    AS ultima_atualizacao
            FROM gold.sismos_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_sismos_estatisticas ON gold.sismos_estatisticas (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.sismos_estatisticas');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.sismos_mapa');
    }
};
