<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rat_relato_vistoria') && ! Schema::hasColumn('rat_relato_vistoria', 'v_municipio_nome')) {
            Schema::table('rat_relato_vistoria', function (Blueprint $table) {
                $table->string('v_municipio_nome', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rat_relato_vistoria') && Schema::hasColumn('rat_relato_vistoria', 'v_municipio_nome')) {
            Schema::table('rat_relato_vistoria', function (Blueprint $table) {
                $table->dropColumn('v_municipio_nome');
            });
        }
    }
};
