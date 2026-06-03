<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rat_relato_dados_gerais: campos de comunicação adicionados via frontend/backend
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            DB::statement("ALTER TABLE rat_relato_dados_gerais ADD COLUMN IF NOT EXISTS com_telefone_contato varchar(20) NULL");
            DB::statement("ALTER TABLE rat_relato_dados_gerais ADD COLUMN IF NOT EXISTS com_nome_solicitante varchar(255) NULL");
        }

        // rat_relato_envolvidos: campos de documento e idade
        if (Schema::hasTable('rat_relato_envolvidos')) {
            DB::statement("ALTER TABLE rat_relato_envolvidos ADD COLUMN IF NOT EXISTS p_uf varchar(2) NULL");
            DB::statement("ALTER TABLE rat_relato_envolvidos ADD COLUMN IF NOT EXISTS p_idade_aparente integer NULL CHECK (p_idade_aparente IS NULL OR (p_idade_aparente >= 0 AND p_idade_aparente <= 150))");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            DB::statement("ALTER TABLE rat_relato_dados_gerais DROP COLUMN IF EXISTS com_telefone_contato");
            DB::statement("ALTER TABLE rat_relato_dados_gerais DROP COLUMN IF EXISTS com_nome_solicitante");
        }

        if (Schema::hasTable('rat_relato_envolvidos')) {
            DB::statement("ALTER TABLE rat_relato_envolvidos DROP COLUMN IF EXISTS p_uf");
            DB::statement("ALTER TABLE rat_relato_envolvidos DROP COLUMN IF EXISTS p_idade_aparente");
        }
    }
};
