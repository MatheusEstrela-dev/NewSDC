<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $relatoTables = [
        'rat_relato_dados_gerais',
        'rat_relato_recursos',
        'rat_relato_envolvidos',
        'rat_relato_vistoria',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->relatoTables as $table) {
            $this->fixOcorrenciaForeignKey($table);
        }

        $this->fixRatAnexosRatIdType();
    }

    public function down(): void
    {
        foreach ($this->relatoTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'ocorrencia_id')) {
                continue;
            }

            $this->dropForeignKeysForColumn($table, 'ocorrencia_id');
        }

        if (Schema::hasTable('rat_anexos') && Schema::hasColumn('rat_anexos', 'rat_id')) {
            DB::statement('ALTER TABLE rat_anexos DROP CONSTRAINT IF EXISTS rat_anexos_rat_id_foreign');
            DB::statement('DROP INDEX IF EXISTS rat_anexos_rat_id_categoria_index');
            DB::statement('DROP INDEX IF EXISTS rat_anexos_rat_id_index');
            DB::statement('ALTER TABLE rat_anexos ALTER COLUMN rat_id TYPE varchar(36) USING rat_id::varchar');
            DB::statement('CREATE INDEX IF NOT EXISTS rat_anexos_rat_id_index ON rat_anexos (rat_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS rat_anexos_rat_id_categoria_index ON rat_anexos (rat_id, categoria)');
        }
    }

    private function fixOcorrenciaForeignKey(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'ocorrencia_id')) {
            return;
        }

        $this->dropForeignKeysForColumn($table, 'ocorrencia_id');

        DB::statement(sprintf(
            'CREATE INDEX IF NOT EXISTS %s ON %s (ocorrencia_id)',
            $this->identifier("{$table}_ocorrencia_id_index"),
            $this->identifier($table),
        ));

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE',
            $this->identifier($table),
            $this->identifier("{$table}_ocorrencia_id_foreign"),
        ));
    }

    private function fixRatAnexosRatIdType(): void
    {
        if (! Schema::hasTable('rat_anexos') || ! Schema::hasColumn('rat_anexos', 'rat_id')) {
            return;
        }

        // Skip if column was already converted to uuid or bigint by a later migration
        $col = DB::selectOne(
            "SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'rat_anexos' AND column_name = 'rat_id'"
        );
        if ($col && in_array($col->data_type, ['uuid', 'bigint', 'integer'])) {
            return;
        }

        DB::statement('ALTER TABLE rat_anexos DROP CONSTRAINT IF EXISTS rat_anexos_rat_id_foreign');
        DB::statement('DROP INDEX IF EXISTS rat_anexos_rat_id_categoria_index');
        DB::statement('DROP INDEX IF EXISTS rat_anexos_rat_id_index');
        DB::statement("ALTER TABLE rat_anexos ALTER COLUMN rat_id TYPE bigint USING NULLIF(rat_id, '')::bigint");
        DB::statement('CREATE INDEX IF NOT EXISTS rat_anexos_rat_id_index ON rat_anexos (rat_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS rat_anexos_rat_id_categoria_index ON rat_anexos (rat_id, categoria)');
        DB::statement('ALTER TABLE rat_anexos ADD CONSTRAINT rat_anexos_rat_id_foreign FOREIGN KEY (rat_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        $constraints = DB::select(<<<SQL
            SELECT conname
            FROM pg_constraint c
            JOIN pg_class rel ON rel.oid = c.conrelid
            JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
            JOIN pg_attribute att ON att.attrelid = rel.oid AND att.attnum = ANY(c.conkey)
            WHERE c.contype = 'f'
              AND nsp.nspname = current_schema()
              AND rel.relname = ?
              AND att.attname = ?
        SQL, [$table, $column]);

        foreach ($constraints as $constraint) {
            DB::statement(sprintf(
                'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
                $this->identifier($table),
                $this->identifier($constraint->conname),
            ));
        }
    }

    private function identifier(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
};
