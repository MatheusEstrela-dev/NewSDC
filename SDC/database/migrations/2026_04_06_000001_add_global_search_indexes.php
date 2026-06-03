<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->index('n_protocolo_fide', 'idx_dec_n_protocolo_fide');
        });

        Schema::table('rats', function (Blueprint $table) {
            $table->index('protocolo', 'idx_rats_protocolo');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('titulo', 'idx_tasks_titulo');
        });

        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->index('num_protocolo', 'idx_pae_num_protocolo');
        });
    }

    public function down(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->dropIndex('idx_dec_n_protocolo_fide');
        });

        Schema::table('rats', function (Blueprint $table) {
            $table->dropIndex('idx_rats_protocolo');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_titulo');
        });

        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->dropIndex('idx_pae_num_protocolo');
        });
    }
};
