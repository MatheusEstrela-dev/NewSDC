<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // dec_entrada_processos - zero indexes, causa raiz do TTFB alto
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->index('reconhecimento', 'idx_proc_reconhecimento');
            $table->index('tipo_desastre', 'idx_proc_tipo_desastre');
            $table->index('data_entrada', 'idx_proc_data_entrada');
            $table->index('data_publicacao_mg', 'idx_proc_data_pub_mg');
            $table->index('tipo_desastre_id', 'idx_proc_tipo_desastre_id');
            $table->index(['reconhecimento', 'tipo_desastre'], 'idx_proc_recon_tipo');
        });

        // dec_decreto_municipios - falta index no FK entrada_processos_id
        Schema::table('dec_decreto_municipios', function (Blueprint $table) {
            $table->index('entrada_processos_id', 'idx_decmun_entrada_proc_id');
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
        });

        Schema::table('dec_decreto_municipios', function (Blueprint $table) {
            $table->dropIndex('idx_decmun_entrada_proc_id');
        });
    }
};
