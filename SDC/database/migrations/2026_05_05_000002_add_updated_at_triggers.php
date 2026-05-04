<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TARGET_TABLES = [
        'rat_relato_dados_gerais' => 'data_atualizacao',
        'rat_relato_envolvidos'   => 'updated_at',
        'rat_relato_recursos'     => 'updated_at',
        'rat_relato_vistorias'    => 'updated_at',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.set_updated_at_column()
            RETURNS trigger AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.set_data_atualizacao_column()
            RETURNS trigger AS $$
            BEGIN
                NEW.data_atualizacao = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach (self::TARGET_TABLES as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $triggerName = "trg_{$table}_set_{$column}";
            $functionName = $column === 'data_atualizacao'
                ? 'public.set_data_atualizacao_column'
                : 'public.set_updated_at_column';

            DB::statement(sprintf('DROP TRIGGER IF EXISTS %s ON %s', $triggerName, $table));
            DB::statement(sprintf(
                'CREATE TRIGGER %s BEFORE UPDATE ON %s FOR EACH ROW EXECUTE FUNCTION %s()',
                $triggerName,
                $table,
                $functionName
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TARGET_TABLES as $table => $column) {
            $triggerName = "trg_{$table}_set_{$column}";
            DB::statement(sprintf('DROP TRIGGER IF EXISTS %s ON %s', $triggerName, $table));
        }

        DB::statement('DROP FUNCTION IF EXISTS public.set_updated_at_column()');
        DB::statement('DROP FUNCTION IF EXISTS public.set_data_atualizacao_column()');
    }
};
