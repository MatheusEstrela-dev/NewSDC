<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Backfill sequencial_ano para registros com valor NULL ─────────
        DB::statement("
            UPDATE rat_ocorrencias
            SET sequencial_ano = EXTRACT(YEAR FROM created_at)::bigint
            WHERE sequencial_ano IS NULL
        ");

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

            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('{$table}', 'id'),
                    GREATEST(1, (SELECT MAX(id) FROM \"{$table}\")),
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
