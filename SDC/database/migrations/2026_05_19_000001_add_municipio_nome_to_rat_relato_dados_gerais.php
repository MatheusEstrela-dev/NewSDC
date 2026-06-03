<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            DB::statement("ALTER TABLE rat_relato_dados_gerais ADD COLUMN IF NOT EXISTS local_municipio_nome varchar(255) NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            DB::statement("ALTER TABLE rat_relato_dados_gerais DROP COLUMN IF EXISTS local_municipio_nome");
        }
    }
};
