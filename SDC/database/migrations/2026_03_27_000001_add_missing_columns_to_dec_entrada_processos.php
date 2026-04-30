<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            if (!Schema::hasColumn('dec_entrada_processos', 'status')) {
                $table->string('status')->nullable()->after('decreto_municipal');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'redec_id')) {
                $table->unsignedBigInteger('redec_id')->nullable()->after('tipo_desastre');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'orgao_responsavel_id')) {
                $table->unsignedBigInteger('orgao_responsavel_id')->nullable()->after('redec_id');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'n_decreto_estadual')) {
                $table->string('n_decreto_estadual')->nullable()->after('prazo_vigencia');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'data_decreto_estadual')) {
                $table->date('data_decreto_estadual')->nullable()->after('n_decreto_estadual');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'n_edicao_domg')) {
                $table->string('n_edicao_domg')->nullable()->after('data_decreto_estadual');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'data_publicacao_domg')) {
                $table->date('data_publicacao_domg')->nullable()->after('n_edicao_domg');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'data_portaria_federal')) {
                $table->date('data_portaria_federal')->nullable()->after('reconhecimento_federal');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'n_edicao_dou')) {
                $table->string('n_edicao_dou')->nullable()->after('data_portaria_federal');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'data_publicacao_dou')) {
                $table->date('data_publicacao_dou')->nullable()->after('n_edicao_dou');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'situacao_processo')) {
                $table->string('situacao_processo')->nullable()->after('processo_inserido_sei');
            }
            if (!Schema::hasColumn('dec_entrada_processos', 'area_afetada_geom')) {
                $table->text('area_afetada_geom')->nullable()->after('situacao_processo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $columns = [
                'status', 'redec_id', 'orgao_responsavel_id',
                'n_decreto_estadual', 'data_decreto_estadual', 'n_edicao_domg', 'data_publicacao_domg',
                'data_portaria_federal', 'n_edicao_dou', 'data_publicacao_dou',
                'situacao_processo', 'area_afetada_geom',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('dec_entrada_processos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
