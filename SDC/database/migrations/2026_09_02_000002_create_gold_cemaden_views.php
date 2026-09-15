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

        // Uma linha por estacao com o snapshot mais recente, igual ao
        // gold.inmet_mapa. Aqui o join nao cruza schemas: a dimensao do CEMADEN
        // e artefato do pipeline, nao cadastro de dominio como o inventario do
        // INMET.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.cemaden_mapa AS
            SELECT DISTINCT ON (l.codigo_estacao)
                l.id,
                l.codigo_estacao,
                e.nome           AS nome_estacao,
                e.municipio,
                e.codigo_ibge,
                e.uf,
                e.tipo,
                l.medido_em,
                ST_Y(e.geom)     AS latitude,
                ST_X(e.geom)     AS longitude,
                e.geom,
                l.acumulado_24h,
                -- Mesmas faixas LHASA_RIO do gold.inmet_mapa, para que as duas
                -- redes usem uma legenda unica na tela. Duplicar o CASE e o
                -- preco de manter as matviews independentes: unificar exigiria
                -- uma funcao SQL compartilhada, e funcao em matview impede o
                -- REFRESH CONCURRENTLY de ser planejado como imutavel.
                CASE
                    WHEN l.acumulado_24h IS NULL THEN 'desconhecido'
                    WHEN l.acumulado_24h =   0   THEN 'sem_chuva'
                    WHEN l.acumulado_24h <   5   THEN 'muito_fraca'
                    WHEN l.acumulado_24h <  15   THEN 'fraca'
                    WHEN l.acumulado_24h <  35   THEN 'moderada'
                    WHEN l.acumulado_24h <  60   THEN 'forte'
                    WHEN l.acumulado_24h < 100   THEN 'muito_forte'
                    WHEN l.acumulado_24h < 140   THEN 'intensa'
                    ELSE 'extrema'
                END AS classe_precipitacao
            FROM silver.leituras_cemaden l
            JOIN silver.estacoes_cemaden e ON e.codigo = l.codigo_estacao
            ORDER BY l.codigo_estacao, l.medido_em DESC
        SQL);

        // UNICO e obrigatorio para o REFRESH ... CONCURRENTLY, que e o que
        // evita travar a leitura do mapa durante o refresh.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_cemaden_mapa_id ON gold.cemaden_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_cemaden_mapa_geom ON gold.cemaden_mapa USING GIST (geom)');

        // ultima_atualizacao aqui e max(medido_em), nao now() como no INMET: e o
        // horario do dado, que e exatamente o que a tela precisa mostrar para
        // provar que andou de 16:10 para 16:20. now() diria apenas quando o
        // refresh rodou, o que nao distingue dado novo de dado repetido.
        //
        // estacoes_sem_telemetria conta o que o feed devolve com acumulado
        // nulo: das 830 de MG, cerca de 476 nao transmitem em cada snapshot.
        // Sem esse numero na tela, "354 estacoes" pareceria perda de dado.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.cemaden_estatisticas AS
            SELECT
                1                                                AS id,
                count(*)                                         AS total_estacoes,
                round(avg(acumulado_24h), 2)                     AS precipitacao_media,
                max(acumulado_24h)                               AS precipitacao_maxima,
                count(*) FILTER (WHERE acumulado_24h > 0)        AS estacoes_com_chuva,
                count(*) FILTER (WHERE acumulado_24h IS NULL)    AS estacoes_sem_telemetria,
                max(medido_em)                                   AS ultima_atualizacao
            FROM gold.cemaden_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_cemaden_estatisticas ON gold.cemaden_estatisticas (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.cemaden_estatisticas');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.cemaden_mapa');
    }
};
