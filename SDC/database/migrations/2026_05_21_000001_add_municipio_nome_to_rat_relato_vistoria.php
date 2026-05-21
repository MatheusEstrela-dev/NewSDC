<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE rat_relato_vistoria ADD COLUMN IF NOT EXISTS v_municipio_nome varchar(255) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE rat_relato_vistoria DROP COLUMN IF EXISTS v_municipio_nome");
    }
};
