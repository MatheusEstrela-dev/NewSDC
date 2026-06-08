<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais') && ! Schema::hasColumn('rat_relato_dados_gerais', 'local_municipio_nome')) {
            Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
                $table->string('local_municipio_nome', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais') && Schema::hasColumn('rat_relato_dados_gerais', 'local_municipio_nome')) {
            Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
                $table->dropColumn('local_municipio_nome');
            });
        }
    }
};
