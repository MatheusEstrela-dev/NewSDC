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

        // ultima_atualizacao era now(), ou seja, o instante do REFRESH. Isso
        // responde "quando a matview foi refeita", que nao e o que a tela
        // pergunta nem o que o operador precisa saber: interessa a idade do
        // DADO. O gold.cemaden_estatisticas ja nascera com max(medido_em), e
        // manter as duas redes com semanticas diferentes no mesmo overlay era
        // comparar coisas distintas lado a lado.
        //
        // DROP e CREATE porque Postgres nao permite CREATE OR REPLACE em
        // materialized view.
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.inmet_estatisticas');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW gold.inmet_estatisticas AS
            SELECT
                1                                        AS id,
                count(*)                                 AS total_estacoes,
                round(avg(precipitacao), 2)              AS precipitacao_media,
                max(precipitacao)                        AS precipitacao_maxima,
                count(*) FILTER (WHERE precipitacao > 0) AS estacoes_com_chuva,
                round(avg(temperatura), 2)               AS temperatura_media,
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

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW gold.inmet_estatisticas AS
            SELECT
                1                                        AS id,
                count(*)                                 AS total_estacoes,
                round(avg(precipitacao), 2)              AS precipitacao_media,
                max(precipitacao)                        AS precipitacao_maxima,
                count(*) FILTER (WHERE precipitacao > 0) AS estacoes_com_chuva,
                round(avg(temperatura), 2)               AS temperatura_media,
                now()                                    AS ultima_atualizacao
            FROM gold.inmet_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_estatisticas ON gold.inmet_estatisticas (id)');
    }
};
