<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove 7 orphaned RAT tables that have no Eloquent model and are never
 * read or written by the application.
 *
 * Active tables kept:
 *   rat_ocorrencias, rat_ocorrencia_relatos, rat_relato_dados_gerais,
 *   rat_relato_recursos, rat_recursos_componentes_guarnicao,
 *   rat_relato_envolvidos, rat_relato_vistoria,
 *   rat_ocorrencia_historico, rat_anexos
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix missing FK on rat_ocorrencia_historico — apenas PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE rat_ocorrencia_historico
                  DROP CONSTRAINT IF EXISTS rat_ocorrencia_historico_ocorrencia_id_foreign
            SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE rat_ocorrencia_historico
                  ADD CONSTRAINT rat_ocorrencia_historico_ocorrencia_id_foreign
                  FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE
            SQL);
        }

        // Drop orphaned tables (CASCADE removes any remaining FK constraints)
        Schema::dropIfExists('rat_ocorrencia_historicos');  // plural duplicate, no model
        Schema::dropIfExists('rat_relato_vistorias');       // plural duplicate, no model
        Schema::dropIfExists('rat_recursos_empregados');    // no model, never used
        Schema::dropIfExists('rat_tipos');                  // seeded but no model/route
        Schema::dropIfExists('rat_redec');                  // reference table, no model
        Schema::dropIfExists('rat_veiculos');               // no model
        Schema::dropIfExists('rats');                       // old monolithic table
    }

    public function down(): void
    {
        // Tables removed intentionally — restore from backup if needed.
    }
};
