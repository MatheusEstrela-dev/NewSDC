<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $extensions = [
            'pgcrypto',
            'pg_trgm',
            'uuid-ossp',
            'unaccent',
            'pg_stat_statements',
            'vector',
            'postgis',
        ];

        foreach ($extensions as $ext) {
            $available = DB::selectOne(
                'SELECT 1 AS ok FROM pg_available_extensions WHERE name = ?',
                [$ext]
            );

            if (! $available) {
                continue;
            }

            try {
                DB::statement(sprintf('CREATE EXTENSION IF NOT EXISTS "%s"', $ext));
            } catch (\Throwable $e) {
                // Em Azure Flexible Server a extensao precisa estar em azure.extensions
                // antes do CREATE. Se nao estiver, skipamos para nao bloquear a migracao
                // do schema. Habilite via Portal -> Server parameters -> azure.extensions
                // e rode `php artisan migrate` novamente para reaplicar.
            }
        }

        try {
            DB::statement(<<<'SQL'
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM pg_available_extensions WHERE name = 'citus') THEN
                        CREATE EXTENSION IF NOT EXISTS citus;
                    END IF;
                END
                $$;
            SQL);
        } catch (\Throwable $e) {
            // Citus nao disponivel em Azure Flexible Server (ok ignorar).
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $extensions = [
            'citus',
            'postgis',
            'vector',
            'pg_stat_statements',
            'unaccent',
            'uuid-ossp',
            'pg_trgm',
            'pgcrypto',
        ];

        foreach ($extensions as $ext) {
            DB::statement(sprintf('DROP EXTENSION IF EXISTS "%s"', $ext));
        }
    }
};
