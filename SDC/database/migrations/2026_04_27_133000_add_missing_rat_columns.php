<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix rat_relato_dados_gerais
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'usuario_id')) {
                    $table->unsignedBigInteger('usuario_id')->nullable()->after('ocorrencia_id');
                }
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'descricao')) {
                    $table->longText('descricao')->nullable()->after('usuario_id');
                }
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'status')) {
                    $table->string('status')->default('rascunho')->after('descricao');
                }
            });
        }

        // Fix rat_relato_envolvidos
        if (Schema::hasTable('rat_relato_envolvidos')) {
            Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_envolvidos', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                }
            });
        }

        // Fix rat_relato_recursos
        if (Schema::hasTable('rat_relato_recursos')) {
            Schema::table('rat_relato_recursos', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_recursos', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                }
            });
        }

        // Fix rat_relato_vistorias
        if (Schema::hasTable('rat_relato_vistorias')) {
            Schema::table('rat_relato_vistorias', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_vistorias', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed for this fix
    }
};
