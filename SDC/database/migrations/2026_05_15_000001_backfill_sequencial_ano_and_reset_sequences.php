<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Backfill sequencial_ano para registros com valor NULL ─────────
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE rat_ocorrencias
                SET sequencial_ano = EXTRACT(YEAR FROM created_at)::bigint
                WHERE sequencial_ano IS NULL
            ");
        } else {
            // SQLite: usa strftime para extrair o ano
            DB::statement("
                UPDATE rat_ocorrencias
                SET sequencial_ano = CAST(strftime('%Y', created_at) AS INTEGER)
                WHERE sequencial_ano IS NULL
            ");
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // ── 2. PostgreSQL: reinicia sequences para o próximo ID correto ──────
        // Garante que o próximo ID gerado continue após o maior ID existente,
        // evitando conflitos caso a tabela tenha sido limpa durante testes.
        $tables = [
            'rat_ocorrencias',
            'rat_ocorrencia_relatos',
            'rat_relato_dados_gerais',
            'rat_relato_recursos',
            'rat_relato_envolvidos',
            'rat_relato_vistoria',
            'rat_recursos_componentes_guarnicao',
        ];

        foreach ($tables as $table) {
            // Só atua se a tabela existir
            $exists = DB::selectOne("
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'public' AND table_name = ?
            ", [$table]);

            if (!$exists) {
                continue;
            }

            // Skip tables where id is not a serial (e.g., UUID primary keys have no sequence)
            $seq = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS seq", [$table]);
            if (empty($seq->seq)) {
                continue;
            }

            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('{$table}', 'id'),
                    GREATEST(1, (SELECT COALESCE(MAX(id), 1) FROM \"{$table}\")),
                    true
                )
            ");
        }
    }

    public function down(): void
    {
        // Não revertemos o backfill de sequencial_ano pois pode haver perda de dados
    }
};
