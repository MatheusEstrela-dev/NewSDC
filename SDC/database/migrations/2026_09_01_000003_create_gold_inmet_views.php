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

        // Uma linha por estacao, com a leitura mais recente. DISTINCT ON e a
        // forma do Postgres de fazer "primeiro por grupo" sem subconsulta.
        //
        // O join com a dimensao cruza schemas (silver -> public) de proposito:
        // a estacao e cadastro de dominio, nao artefato do pipeline.
        //
        // WHERE geom IS NOT NULL: estacao sem coordenada nao pode aparecer no
        // mapa, nem plotada em (0,0).
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_mapa AS
            SELECT DISTINCT ON (l.codigo_estacao)
                l.id,
                l.codigo_estacao,
                e.nome            AS nome_estacao,
                e.municipio,
                e.uf,
                l.medido_em,
                ST_Y(e.geom)      AS latitude,
                ST_X(e.geom)      AS longitude,
                e.geom,
                l.temperatura,
                l.umidade,
                l.precipitacao,
                l.velocidade_vento,
                l.pressao,
                -- Faixas do sistema LHASA_RIO adaptadas para MG, as mesmas que
                -- a legenda da pagina ja documentava. Classificar no banco (e
                -- nao no PHP) e o que mantem a entrega sem agregacao.
                CASE
                    WHEN l.precipitacao IS NULL THEN 'desconhecido'
                    WHEN l.precipitacao =   0   THEN 'sem_chuva'
                    WHEN l.precipitacao <   5   THEN 'muito_fraca'
                    WHEN l.precipitacao <  15   THEN 'fraca'
                    WHEN l.precipitacao <  35   THEN 'moderada'
                    WHEN l.precipitacao <  60   THEN 'forte'
                    WHEN l.precipitacao < 100   THEN 'muito_forte'
                    WHEN l.precipitacao < 140   THEN 'intensa'
                    ELSE 'extrema'
                END AS classe_precipitacao
            FROM silver.leituras_inmet l
            JOIN estacoes_meteorologicas e ON e.codigo = l.codigo_estacao
            WHERE e.geom IS NOT NULL
            ORDER BY l.codigo_estacao, l.medido_em DESC
        SQL);

        // Indice UNICO e obrigatorio para REFRESH ... CONCURRENTLY, e o
        // CONCURRENTLY e o que evita travar a leitura do mapa no refresh.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_mapa_id ON gold.inmet_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_inmet_mapa_geom ON gold.inmet_mapa USING GIST (geom)');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_estatisticas AS
            SELECT
                1                                        AS id,
                count(*)                                 AS total_estacoes,
                round(avg(precipitacao), 2)              AS precipitacao_media,
                max(precipitacao)                        AS precipitacao_maxima,
                count(*) FILTER (WHERE precipitacao > 0) AS estacoes_com_chuva,
                round(avg(temperatura), 2)               AS temperatura_media,
                -- Idade do DADO, nao do refresh: e o que a tela pergunta.
                -- Espelha o gold.cemaden_estatisticas, para que as duas
                -- redes signifiquem a mesma coisa no mesmo overlay.
                max(medido_em)                           AS ultima_atualizacao
            FROM gold.inmet_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_estatisticas ON gold.inmet_estatisticas (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.inmet_estatisticas');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.inmet_mapa');
    }
};
