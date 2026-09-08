<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Habilita o arquivamento de camada em bases que JA rodaram a migration de
     * origem.
     *
     * A regra 9 do projeto foi cumprida: o arquivamento esta escrito em
     * 2026_09_03_000003, que e a migration principal. O problema e que ela usa
     * CREATE TABLE IF NOT EXISTS -- em base existente ela nao faz nada, e as
     * colunas novas nunca apareceriam.
     *
     * A consequencia de omitir esta corretiva nao seria erro visivel: o CHECK
     * ck_silver_geo_camadas_status continuaria recusando 'arquivada', e cada
     * tentativa de arquivar morreria com violacao de check no meio da acao do
     * revisor. Por isso vai migration, e nao DDL aplicado a mao.
     *
     * Idempotente: em base nova, que ja nasceu correta pela principal, nao
     * muda nada.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE silver.geo_camadas
              ADD COLUMN IF NOT EXISTS arquivado_por       bigint      NULL,
              ADD COLUMN IF NOT EXISTS arquivado_em        timestamptz NULL,
              ADD COLUMN IF NOT EXISTS motivo_arquivamento text        NULL
        SQL);

        // ADD CONSTRAINT nao aceita IF NOT EXISTS no Postgres, entao o bloco
        // condicional le pg_constraint.
        DB::unprepared(<<<'SQL'
            DO $$
            BEGIN
              IF NOT EXISTS (
                SELECT 1 FROM pg_constraint
                 WHERE conname = 'silver_geo_camadas_arquivado_por_foreign'
              ) THEN
                ALTER TABLE silver.geo_camadas
                  ADD CONSTRAINT silver_geo_camadas_arquivado_por_foreign
                  FOREIGN KEY (arquivado_por) REFERENCES users (id) ON DELETE SET NULL;
              END IF;
            END $$;
        SQL);

        /*
         * O CHECK e RECRIADO, e nao adicionado: ele existe e recusa
         * 'arquivada'. Trocar constraint de dominio nao tem forma incremental
         * no Postgres -- e DROP seguido de ADD.
         *
         * A janela entre os dois comandos e coberta pela transacao implicita da
         * migration: se o ADD falhar, o DROP volta, e a tabela nao fica sem
         * validacao de status.
         */
        DB::unprepared(<<<'SQL'
            DO $$
            BEGIN
              IF EXISTS (
                SELECT 1 FROM pg_constraint
                 WHERE conname = 'ck_silver_geo_camadas_status'
              ) THEN
                ALTER TABLE silver.geo_camadas DROP CONSTRAINT ck_silver_geo_camadas_status;
              END IF;

              ALTER TABLE silver.geo_camadas ADD CONSTRAINT ck_silver_geo_camadas_status
                CHECK (status IN ('pendente', 'aprovada', 'recusada', 'arquivada'));
            END $$;
        SQL);

        // A varredura de validade vencida filtra por status e valido_ate.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_validade ON silver.geo_camadas (status, valido_ate)');

        /*
         * Gold NAO muda, de proposito.
         *
         * gold.geo_feicao_mapa e gold.geo_camada_municipios filtram por
         * `c.status = 'aprovada'`, entao 'arquivada' ja fica fora do mapa sem
         * uma linha de SQL nova. Foi por isso que o arquivamento virou estado
         * de status em vez de coluna `arquivado_em` consultada a parte: um
         * predicado de data DENTRO da matview seria avaliado no refresh, e a
         * camada vencida continuaria publicada ate o proximo refresh
         * acontecer -- que e justamente o que nao pode acontecer em mapa de
         * plantao.
         */
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Volta o CHECK ao dominio anterior. As camadas arquivadas precisam sair
        // de 'arquivada' antes, senao o ADD CONSTRAINT falha com as linhas
        // existentes -- voltam a 'aprovada', que era o estado delas antes do
        // arquivamento.
        DB::statement("UPDATE silver.geo_camadas SET status = 'aprovada' WHERE status = 'arquivada'");

        DB::unprepared(<<<'SQL'
            DO $$
            BEGIN
              IF EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'ck_silver_geo_camadas_status'
              ) THEN
                ALTER TABLE silver.geo_camadas DROP CONSTRAINT ck_silver_geo_camadas_status;
              END IF;

              ALTER TABLE silver.geo_camadas ADD CONSTRAINT ck_silver_geo_camadas_status
                CHECK (status IN ('pendente', 'aprovada', 'recusada'));
            END $$;
        SQL);

        DB::statement('DROP INDEX IF EXISTS silver.idx_silver_geo_camadas_validade');

        DB::statement(<<<'SQL'
            ALTER TABLE silver.geo_camadas
              DROP COLUMN IF EXISTS arquivado_por,
              DROP COLUMN IF EXISTS arquivado_em,
              DROP COLUMN IF EXISTS motivo_arquivamento
        SQL);
    }
};
