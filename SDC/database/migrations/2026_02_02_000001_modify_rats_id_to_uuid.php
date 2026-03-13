<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ATENÇÃO: Esta migração é destrutiva para dados existentes se houver FKs não tratadas.
        // Como é um ambiente de desenvolvimento/novo módulo, vamos converter.
        
        if (Schema::hasTable('rats')) {
            // 1. Drop Primary Key
            Schema::table('rats', function (Blueprint $table) {
                // MySQL não permite mudar tipo de coluna PK diretamente facilmente se for auto-increment
                // Primeiro removemos o auto-increment (modificando a coluna)
                $table->unsignedBigInteger('id')->change();
                $table->dropPrimary();
            });

            // 2. Change column type to Char(36)
            Schema::table('rats', function (Blueprint $table) {
                $table->char('id', 36)->change();
                $table->primary('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversão complexa omitida por segurança em dev
    }
};
