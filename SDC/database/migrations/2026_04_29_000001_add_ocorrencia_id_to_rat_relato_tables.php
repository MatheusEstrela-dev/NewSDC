<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rat_relato_dados_gerais
        Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
            if (!Schema::hasColumn('rat_relato_dados_gerais', 'ocorrencia_id')) {
                $table->unsignedBigInteger('ocorrencia_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('rat_relato_dados_gerais', 'descricao')) {
                $table->text('descricao')->nullable()->after('tem_vistoria');
            }
            if (!Schema::hasColumn('rat_relato_dados_gerais', 'status')) {
                $table->string('status', 50)->nullable()->after('descricao');
            }
        });

        // rat_relato_envolvidos
        Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
            if (!Schema::hasColumn('rat_relato_envolvidos', 'ocorrencia_id')) {
                $table->unsignedBigInteger('ocorrencia_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('rat_relato_envolvidos', 'seq')) {
                $table->integer('seq')->nullable()->after('ocorrencia_id');
            }
        });

        // rat_relato_recursos
        Schema::table('rat_relato_recursos', function (Blueprint $table) {
            if (!Schema::hasColumn('rat_relato_recursos', 'ocorrencia_id')) {
                $table->unsignedBigInteger('ocorrencia_id')->nullable()->index()->after('id');
            }
        });

        // rat_relato_vistoria
        Schema::table('rat_relato_vistoria', function (Blueprint $table) {
            if (!Schema::hasColumn('rat_relato_vistoria', 'ocorrencia_id')) {
                $table->unsignedBigInteger('ocorrencia_id')->nullable()->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
            $table->dropColumn(array_filter(['ocorrencia_id', 'descricao', 'status'], fn($c) => Schema::hasColumn('rat_relato_dados_gerais', $c)));
        });
        Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
            $table->dropColumn(array_filter(['ocorrencia_id', 'seq'], fn($c) => Schema::hasColumn('rat_relato_envolvidos', $c)));
        });
        Schema::table('rat_relato_recursos', function (Blueprint $table) {
            if (Schema::hasColumn('rat_relato_recursos', 'ocorrencia_id')) $table->dropColumn('ocorrencia_id');
        });
        Schema::table('rat_relato_vistoria', function (Blueprint $table) {
            if (Schema::hasColumn('rat_relato_vistoria', 'ocorrencia_id')) $table->dropColumn('ocorrencia_id');
        });
    }
};
