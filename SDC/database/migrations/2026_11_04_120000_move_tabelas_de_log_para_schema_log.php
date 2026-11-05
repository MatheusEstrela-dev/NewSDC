<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tabelas de log no schema `log` do proprio banco sdc. Migration principal do
 * armazenamento de log: tabela de log nova entra na lista abaixo.
 *
 * Armazenamento e conexao sao independentes. A conexao (pgsql_log, ver
 * ConexaoDeLog) separa as sessoes; o schema separa o armazenamento, para a
 * manutencao enxergar e tratar o log num lugar so (DBeaver, GRANT, backup e
 * limpeza por schema). Por isso entram tambem logs que continuam na conexao
 * principal por amarracao transacional (dec_entrada_processo_logs,
 * task_audit_logs) e os legados sem uso.
 *
 * SET SCHEMA so mexe em metadado: instantaneo, sem copiar linha. Indices,
 * sequencias e FKs vao junto e continuam valendo (mesmo banco). O codigo nao
 * qualifica `log.`: o search_path das conexoes que leem o sdc inclui `log`
 * depois do schema principal (config/database.php), e o banco ganha o mesmo
 * search_path como default para quem nao configura sessao (DBeaver, psql,
 * PgBouncer em transaction mode).
 *
 * Migration futura que CRIA tabela de log usa Schema::create('log.nome'); a que
 * checa existencia usa Schema::hasTable('log.nome') -- sem o schema, o Laravel
 * so olha o primeiro schema do search_path.
 */
return new class extends Migration
{
    private const SCHEMA = 'log';

    private const TABELAS = [
        // Notificacoes
        'notifications',
        'notifications_archive',
        // Tecnicos
        'failed_jobs',
        'webhook_logs',
        'webhook_events',
        'webhook_events_archive',
        'request_traces',
        'ai_execution_logs',
        'compdec_etl_log',
        'cisterna_etl_log',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        // Auditoria
        'audit_logs',
        'permission_audit_log',
        'user_status_histories',
        'acessos_auditoria',
        'nota_tecnica_acessos',
        'task_audit_logs',
        // Historico dos modulos
        'pae_timeline',
        'rat_ocorrencia_historico',
        'tdap_historicos',
        'pmda_plano_eventos',
        'dec_entrada_processo_logs',
        // Legados sem uso
        'processo_logs',
        'dec_log',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE SCHEMA IF NOT EXISTS '.self::SCHEMA);

        $this->mover('public', self::SCHEMA);
        $this->ajustarSearchPathDoBanco(true);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->mover(self::SCHEMA, 'public');
        $this->ajustarSearchPathDoBanco(false);
    }

    /**
     * Default do banco para sessoes que nao setam search_path (DBeaver, psql,
     * PgBouncer). Acrescenta ou retira so o `log`, preservando o resto: o
     * banco ja traz "$user", public, tiger, topology do PostGIS, e um SET fixo
     * apagaria os schemas do geocoder. Sem ser dono do banco, segue sem ele: a
     * aplicacao seta o proprio search_path na conexao.
     */
    private function ajustarSearchPathDoBanco(bool $incluir): void
    {
        $novo = $incluir
            ? "CASE WHEN atual ~ '(^|,\\s*)log\\s*(,|$)' THEN NULL ELSE atual || ', log' END"
            : "CASE WHEN atual ~ '(^|,\\s*)log\\s*(,|$)' THEN regexp_replace(atual, ',\\s*log\\s*(,|$)', '\\1') ELSE NULL END";

        DB::statement(<<<SQL
            DO \$\$
            DECLARE
                atual text;
                novo text;
            BEGIN
                SELECT substring(c FROM '^search_path=(.*)$') INTO atual
                FROM pg_db_role_setting s
                JOIN pg_database d ON d.oid = s.setdatabase,
                LATERAL unnest(s.setconfig) c
                WHERE d.datname = current_database() AND s.setrole = 0 AND c LIKE 'search_path=%';

                atual := coalesce(atual, '"\$user", public');
                novo := {$novo};

                IF novo IS NOT NULL THEN
                    EXECUTE format('ALTER DATABASE %I SET search_path TO %s', current_database(), novo);
                END IF;
            EXCEPTION WHEN insufficient_privilege THEN
                RAISE NOTICE 'search_path default do banco nao alterado: sem privilegio';
            END
            \$\$
            SQL);
    }

    /**
     * Move cada tabela que exista na origem e ainda nao exista no destino.
     * Idempotente: rodar de novo, ou num banco sem alguma tabela, nao falha.
     */
    private function mover(string $origem, string $destino): void
    {
        $existentes = DB::table('pg_tables')
            ->whereIn('schemaname', [$origem, $destino])
            ->whereIn('tablename', self::TABELAS)
            ->get(['schemaname', 'tablename']);

        $noDestino = $existentes->where('schemaname', $destino)->pluck('tablename')->all();

        foreach ($existentes->where('schemaname', $origem) as $tabela) {
            if (in_array($tabela->tablename, $noDestino, true)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE %s.%s SET SCHEMA %s',
                $origem,
                $tabela->tablename,
                $destino,
            ));
        }
    }
};
