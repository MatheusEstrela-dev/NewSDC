<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Garante ranking.atividade em bancos cuja primeira migration foi aplicada
 * ANTES de a tabela entrar nela (dev). A migration original nao pode ser
 * reexecutada nem editada: ja consta como aplicada em ambientes com dados.
 * Idempotente: onde a tabela existe (homolog, bancos novos) nao faz nada.
 *
 * Aplica so na conexao `ranking`, via entrypoint:
 *   php artisan migrate --database=ranking --path=database/migrations/ranking --force
 */
return new class extends Migration
{
    protected $connection = 'ranking';

    private const DESTINOS_PROIBIDOS = ['sdc', 'forge'];

    public function up(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino($db);

        $db->statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.atividade (
                id            bigserial PRIMARY KEY,
                periodo_id    bigint      NOT NULL REFERENCES ranking.periodos (id) ON DELETE CASCADE,
                escopo        varchar(12) NOT NULL,
                entidade_id   bigint      NOT NULL,
                dias_ativos   integer     NOT NULL DEFAULT 0,
                atualizado_em timestamptz NOT NULL DEFAULT now(),

                CONSTRAINT ck_ranking_atividade_escopo CHECK (escopo IN ('usuario', 'orgao', 'municipio')),
                CONSTRAINT ck_ranking_atividade_dias   CHECK (dias_ativos >= 0),
                CONSTRAINT uq_ranking_atividade UNIQUE (periodo_id, escopo, entidade_id)
            )
        SQL);
    }

    public function down(): void
    {
        // Sem reversao: a tabela pertence a migration original.
    }

    private function validarDestino(\Illuminate\Database\Connection $db): void
    {
        // Em --pretend nao ha query executada; nada a proteger.
        if ($db->pretending()) {
            return;
        }

        $atual = (string) $db->selectOne('SELECT current_database() AS db')->db;

        if (in_array($atual, self::DESTINOS_PROIBIDOS, true)) {
            throw new RuntimeException(
                "Migration de ranking recusada: destino '{$atual}' e base operacional."
            );
        }
    }
};
