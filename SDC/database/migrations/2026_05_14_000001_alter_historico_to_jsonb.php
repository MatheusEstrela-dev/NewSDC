<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite não tem JSONB; o cast 'array' do Eloquent já funciona com TEXT.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // PostgreSQL: converte historico de TEXT para JSONB.
        // COALESCE garante que valores NULL e texto inválido virem '[]'.
        DB::statement("
            ALTER TABLE rat_ocorrencias
            ALTER COLUMN historico TYPE jsonb
            USING COALESCE(
                CASE
                    WHEN historico IS NULL OR trim(historico) = '' THEN '[]'
                    WHEN historico ~ '^\\s*[\\[{]' THEN historico::jsonb
                    ELSE '[]'
                END,
                '[]'::jsonb
            )
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            ALTER TABLE rat_ocorrencias
            ALTER COLUMN historico TYPE text
            USING historico::text
        ");
    }
};
