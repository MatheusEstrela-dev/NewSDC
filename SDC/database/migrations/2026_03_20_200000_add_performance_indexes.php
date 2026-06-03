<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_reconhecimento')) {
                $table->index('reconhecimento', 'idx_proc_reconhecimento');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_tipo_desastre')) {
                $table->index('tipo_desastre', 'idx_proc_tipo_desastre');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_data_entrada')) {
                $table->index('data_entrada', 'idx_proc_data_entrada');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_data_pub_mg')) {
                $table->index('data_publicacao_mg', 'idx_proc_data_pub_mg');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_tipo_desastre_id')) {
                $table->index('tipo_desastre_id', 'idx_proc_tipo_desastre_id');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_recon_tipo')) {
                $table->index(['reconhecimento', 'tipo_desastre'], 'idx_proc_recon_tipo');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_processo')) {
                $table->index('processo', 'idx_proc_processo');
            }
            if (!Schema::hasIndex('dec_entrada_processos', 'idx_proc_vigencia')) {
                $table->index(['data_publicacao_mg', 'prazo_vigencia'], 'idx_proc_vigencia');
            }
        });

        Schema::table('dec_decreto_municipios', function (Blueprint $table) {
            if (!Schema::hasIndex('dec_decreto_municipios', 'idx_decmun_entrada_proc_id')) {
                $table->index('entrada_processos_id', 'idx_decmun_entrada_proc_id');
            }
            if (!Schema::hasIndex('dec_decreto_municipios', 'idx_decmun_proc_mun')) {
                $table->index(['entrada_processos_id', 'municipio_id'], 'idx_decmun_proc_mun');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->dropIndex('idx_proc_reconhecimento');
            $table->dropIndex('idx_proc_tipo_desastre');
            $table->dropIndex('idx_proc_data_entrada');
            $table->dropIndex('idx_proc_data_pub_mg');
            $table->dropIndex('idx_proc_tipo_desastre_id');
            $table->dropIndex('idx_proc_recon_tipo');
            $table->dropIndex('idx_proc_processo');
            $table->dropIndex('idx_proc_vigencia');
        });

        Schema::table('dec_decreto_municipios', function (Blueprint $table) {
            $table->dropIndex('idx_decmun_entrada_proc_id');
            $table->dropIndex('idx_decmun_proc_mun');
        });
    }
};
