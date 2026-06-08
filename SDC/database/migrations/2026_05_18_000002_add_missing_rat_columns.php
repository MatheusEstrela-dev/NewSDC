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
            Schema::table('rat_relato_dados_gerais', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (! Schema::hasColumn('rat_relato_dados_gerais', 'com_telefone_contato')) {
                    $table->string('com_telefone_contato', 20)->nullable();
                }
                if (! Schema::hasColumn('rat_relato_dados_gerais', 'com_nome_solicitante')) {
                    $table->string('com_nome_solicitante', 255)->nullable();
                }
            });
        }

        // rat_relato_envolvidos: campos de documento e idade
        if (Schema::hasTable('rat_relato_envolvidos')) {
            Schema::table('rat_relato_envolvidos', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (! Schema::hasColumn('rat_relato_envolvidos', 'p_uf')) {
                    $table->string('p_uf', 2)->nullable();
                }
                if (! Schema::hasColumn('rat_relato_envolvidos', 'p_idade_aparente')) {
                    $table->integer('p_idade_aparente')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rat_relato_dados_gerais')) {
            Schema::table('rat_relato_dados_gerais', function (\Illuminate\Database\Schema\Blueprint $table) {
                $columns = array_filter(['com_telefone_contato', 'com_nome_solicitante'],
                    fn ($c) => Schema::hasColumn('rat_relato_dados_gerais', $c));
                if ($columns) {
                    $table->dropColumn(array_values($columns));
                }
            });
        }

        if (Schema::hasTable('rat_relato_envolvidos')) {
            Schema::table('rat_relato_envolvidos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $columns = array_filter(['p_uf', 'p_idade_aparente'],
                    fn ($c) => Schema::hasColumn('rat_relato_envolvidos', $c));
                if ($columns) {
                    $table->dropColumn(array_values($columns));
                }
            });
        }
    }
};
