<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The UUID migration (2026_05_18_000001) incorrectly targeted 'recurso_id' instead of
        // 'relato_recurso_id' when converting rat_recursos_componentes_guarnicao FK to UUID.
        // This left relato_recurso_id as bigint while the app inserts UUID values — causing 500.

        // 1. Drop wrong FK and column added by the UUID migration
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP CONSTRAINT IF EXISTS rat_recursos_componentes_guarnicao_recurso_id_foreign');
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP COLUMN IF EXISTS recurso_id');

        // 2. Safe to truncate: UUID migration already wiped data via CASCADE from rat_ocorrencias
        DB::statement('TRUNCATE TABLE rat_recursos_componentes_guarnicao');

        // 3. Replace bigint relato_recurso_id with uuid
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP COLUMN IF EXISTS relato_recurso_id');
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao ADD COLUMN relato_recurso_id uuid');

        // 4. Restore index
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rat_recursos_componentes_guarnicao_relato_recurso_id ON rat_recursos_componentes_guarnicao (relato_recurso_id)');

        // 5. Add correct FK
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao ADD CONSTRAINT rat_recursos_componentes_guarnicao_relato_recurso_id_foreign FOREIGN KEY (relato_recurso_id) REFERENCES rat_relato_recursos(id) ON DELETE CASCADE');
    }

    public function down(): void {}
};
