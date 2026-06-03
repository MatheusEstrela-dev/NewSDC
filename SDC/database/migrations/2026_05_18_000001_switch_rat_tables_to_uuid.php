<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Truncate all RAT data (CASCADE empties child tables) ──────────
        DB::statement('TRUNCATE TABLE rat_ocorrencias CASCADE');

        // ── 2. Drop ALL FK constraints that reference rat_ocorrencias ────────
        $fkConstraints = [
            'rat_ocorrencia_historico'           => 'rat_hist_ocorrencia_id_fk',
            'rat_ocorrencia_historicos'          => 'rat_ocorrencia_historicos_ocorrencia_id_foreign',
            'rat_ocorrencia_relatos'             => 'rat_ocorrencia_relatos_ocorrencia_id_foreign',
            'rat_ocorrencias'                    => 'rat_ocorrencias_ocorrencia_origem_id_foreign',
            'rat_relato_dados_gerais'            => 'rat_relato_dados_gerais_ocorrencia_id_foreign',
            'rat_relato_envolvidos'              => 'rat_relato_envolvidos_ocorrencia_id_foreign',
            'rat_relato_recursos'                => 'rat_relato_recursos_ocorrencia_id_foreign',
            'rat_relato_vistoria'                => 'rat_relato_vistoria_ocorrencia_id_foreign',
            'rat_relato_vistorias'               => 'rat_relato_vistorias_ocorrencia_id_foreign',
        ];

        foreach ($fkConstraints as $table => $constraint) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS \"{$constraint}\"");
        }

        // FK from guarnicao → rat_relato_recursos
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP CONSTRAINT IF EXISTS rat_recursos_componentes_guarnicao_recurso_id_foreign');
        DB::statement('ALTER TABLE rat_recursos_empregados DROP CONSTRAINT IF EXISTS rat_recursos_empregados_relato_recurso_id_foreign');

        // FK from rat_anexos → rat_ocorrencias (try both possible names)
        DB::statement('ALTER TABLE rat_anexos DROP CONSTRAINT IF EXISTS rat_anexos_rat_id_foreign');
        DB::statement('ALTER TABLE rat_anexos DROP CONSTRAINT IF EXISTS rat_anexos_ocorrencia_id_foreign');

        // ── 3. Switch PK (id) from bigint to uuid on every RAT table ─────────
        $pkTables = [
            'rat_ocorrencias',
            'rat_ocorrencia_relatos',
            'rat_relato_dados_gerais',
            'rat_relato_recursos',
            'rat_recursos_componentes_guarnicao',
            'rat_relato_envolvidos',
            'rat_relato_vistoria',
            'rat_relato_vistorias',
            'rat_ocorrencia_historicos',
            'rat_ocorrencia_historico',
            'rat_anexos',
        ];

        foreach ($pkTables as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_pkey");
            // Must drop bigserial default before changing type (prevents 42804 datatype mismatch)
            DB::statement("ALTER TABLE {$table} ALTER COLUMN id DROP DEFAULT");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN id TYPE uuid USING gen_random_uuid()");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN id SET DEFAULT gen_random_uuid()");
            DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY (id)");
            DB::statement("DROP SEQUENCE IF EXISTS {$table}_id_seq");
        }

        // ── 4. Switch FK columns from bigint to uuid (drop + re-add) ────────
        // Dropping and re-adding is the cleanest for empty tables.

        // rat_ocorrencias — self-referential (nullable)
        DB::statement('ALTER TABLE rat_ocorrencias DROP COLUMN IF EXISTS ocorrencia_origem_id');
        DB::statement('ALTER TABLE rat_ocorrencias ADD COLUMN ocorrencia_origem_id uuid');

        // rat_ocorrencia_relatos — ocorrencia_id + conteudo_id (polymorphic)
        DB::statement('ALTER TABLE rat_ocorrencia_relatos DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_ocorrencia_relatos ADD COLUMN ocorrencia_id uuid NOT NULL');
        DB::statement('ALTER TABLE rat_ocorrencia_relatos DROP COLUMN IF EXISTS conteudo_id');
        DB::statement('ALTER TABLE rat_ocorrencia_relatos ADD COLUMN conteudo_id uuid NOT NULL');

        // rat_relato_dados_gerais
        DB::statement('ALTER TABLE rat_relato_dados_gerais DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_relato_dados_gerais ADD COLUMN ocorrencia_id uuid');

        // rat_relato_recursos
        DB::statement('ALTER TABLE rat_relato_recursos DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_relato_recursos ADD COLUMN ocorrencia_id uuid');

        // rat_recursos_componentes_guarnicao — FK column is relato_recurso_id, not recurso_id
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP COLUMN IF EXISTS recurso_id');
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao DROP COLUMN IF EXISTS relato_recurso_id');
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao ADD COLUMN relato_recurso_id uuid');

        // rat_relato_envolvidos
        DB::statement('ALTER TABLE rat_relato_envolvidos DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_relato_envolvidos ADD COLUMN ocorrencia_id uuid');

        // rat_relato_vistoria
        DB::statement('ALTER TABLE rat_relato_vistoria DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_relato_vistoria ADD COLUMN ocorrencia_id uuid');

        // rat_relato_vistorias (legacy duplicate)
        DB::statement('ALTER TABLE rat_relato_vistorias DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_relato_vistorias ADD COLUMN ocorrencia_id uuid');

        // rat_ocorrencia_historicos
        DB::statement('ALTER TABLE rat_ocorrencia_historicos DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_ocorrencia_historicos ADD COLUMN ocorrencia_id uuid');

        // rat_ocorrencia_historico (legacy)
        DB::statement('ALTER TABLE rat_ocorrencia_historico DROP COLUMN IF EXISTS ocorrencia_id');
        DB::statement('ALTER TABLE rat_ocorrencia_historico ADD COLUMN ocorrencia_id uuid');

        // rat_anexos — uses rat_id (not ocorrencia_id)
        DB::statement('ALTER TABLE rat_anexos DROP COLUMN IF EXISTS rat_id');
        DB::statement('ALTER TABLE rat_anexos ADD COLUMN rat_id uuid');

        // ── 5. Re-add FK constraints ──────────────────────────────────────────
        DB::statement('ALTER TABLE rat_ocorrencias ADD CONSTRAINT rat_ocorrencias_ocorrencia_origem_id_foreign FOREIGN KEY (ocorrencia_origem_id) REFERENCES rat_ocorrencias(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE rat_ocorrencia_relatos ADD CONSTRAINT rat_ocorrencia_relatos_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_relato_dados_gerais ADD CONSTRAINT rat_relato_dados_gerais_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_relato_recursos ADD CONSTRAINT rat_relato_recursos_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_recursos_componentes_guarnicao ADD CONSTRAINT rat_recursos_componentes_guarnicao_relato_recurso_id_foreign FOREIGN KEY (relato_recurso_id) REFERENCES rat_relato_recursos(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_relato_envolvidos ADD CONSTRAINT rat_relato_envolvidos_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_relato_vistoria ADD CONSTRAINT rat_relato_vistoria_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_ocorrencia_historicos ADD CONSTRAINT rat_ocorrencia_historicos_ocorrencia_id_foreign FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rat_anexos ADD CONSTRAINT rat_anexos_rat_id_foreign FOREIGN KEY (rat_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Intentionally empty — this migration is destructive (data truncated).
    }
};
