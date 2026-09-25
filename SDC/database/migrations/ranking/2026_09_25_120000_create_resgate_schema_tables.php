<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Modulo Resgate - schema `resgate` dentro de `sdc_ranking`.
 *
 * Plano: docs/superpowers/plans/2026-09-25-resgate-pontos-catalogo.md
 *
 * MIGRATION PRINCIPAL DO SCHEMA. As fases seguintes do plano (catalogo,
 * pedidos, eventos, documentos, bloqueios) entram AQUI, consolidadas, e nao em
 * migrations novas.
 *
 * Aplica-se como a do ranking, so contra a conexao `ranking`:
 *
 *   php artisan migrate --database=ranking --path=database/migrations/ranking
 *
 * MESMA DATABASE DO LEDGER, DE PROPOSITO
 * A reserva de pontos le `ranking.lancamentos` e grava `resgate.movimentos` na
 * mesma transacao. Em outra base a consistencia dependeria de compensacao
 * distribuida.
 *
 * APPEND-ONLY NO BANCO
 * As tabelas de trilha recusam UPDATE e DELETE por trigger, e nao so pela
 * aplicacao: o historico de resgate precisa resistir a escrita direta, porque
 * pode virar prova em processo.
 */
return new class extends Migration
{
    protected $connection = 'ranking';

    private const DESTINOS_PROIBIDOS = ['sdc', 'forge'];

    public function up(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('CREATE SCHEMA IF NOT EXISTS resgate');

        // Trava de trilha: qualquer UPDATE ou DELETE aborta. Correcao e um
        // movimento novo (liberacao, estorno de debito), nunca edicao.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.recusar_alteracao() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'resgate.%: registro append-only, % recusado', TG_TABLE_NAME, TG_OP
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$
        SQL);

        // Carteira do ente. O saldo resgatavel e derivado do ledger; aqui so
        // entra o que o RESGATE fez com ele: reservar, liberar, debitar e
        // estornar debito. Pontos com sinal: reserva e debito negativos.
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.movimentos (
                id            bigserial PRIMARY KEY,
                ente_escopo   varchar(12)  NOT NULL,
                ente_id       bigint       NOT NULL,
                tipo          varchar(20)  NOT NULL,
                pontos        integer      NOT NULL,
                pedido_id     bigint       NULL,
                chave         varchar(160) NOT NULL,
                criado_em     timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT ck_resgate_movimentos_escopo CHECK (ente_escopo IN ('municipio', 'orgao')),
                CONSTRAINT ck_resgate_movimentos_tipo CHECK (tipo IN ('reserva', 'liberacao', 'debito', 'estorno_debito')),
                CONSTRAINT ck_resgate_movimentos_sinal CHECK (
                    (tipo IN ('reserva', 'debito') AND pontos < 0)
                    OR (tipo IN ('liberacao', 'estorno_debito') AND pontos > 0)
                ),
                -- Idempotencia: a mesma transicao reprocessada nao duplica movimento.
                CONSTRAINT uq_resgate_movimentos_chave UNIQUE (chave)
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_movimentos_ente ON resgate.movimentos (ente_escopo, ente_id)');
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_movimentos_append_only ON resgate.movimentos');
        $this->exec('CREATE TRIGGER tg_resgate_movimentos_append_only BEFORE UPDATE OR DELETE ON resgate.movimentos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        // Nenhum objeto fora de `resgate` referencia estas tabelas.
        $this->exec('DROP SCHEMA IF EXISTS resgate CASCADE');
    }

    private function conexao(): \Illuminate\Database\Connection
    {
        return DB::connection($this->connection);
    }

    private function exec(string $sql): void
    {
        $this->conexao()->statement($sql);
    }

    /** Recusa aplicar o schema de resgate sobre a base operacional. */
    private function validarDestino(): void
    {
        if ($this->conexao()->pretending()) {
            return;
        }

        $atual = (string) $this->conexao()->selectOne('SELECT current_database() AS db')->db;

        if (in_array($atual, self::DESTINOS_PROIBIDOS, true)) {
            throw new RuntimeException(
                "Migration de resgate recusada: destino '{$atual}' e base operacional. "
                . 'Aplique com --database=ranking apontando para sdc_ranking.'
            );
        }
    }
};
