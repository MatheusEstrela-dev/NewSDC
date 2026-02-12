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
        Schema::create('pae_polif', function (Blueprint $table) {
            // id_polif bigint unsigned NOT NULL AUTO_INCREMENT
            $table->bigIncrements('id_polif');

            // Colunas para o polimorfismo (polif_type e polif_id)
            // O polif_id é BIGINT UNSIGNED conforme definimos para todo o banco
            $table->string('polif_type', 100);
            $table->unsignedBigInteger('polif_id');

            $table->timestamps();

            // Índice composto crucial para buscas polimórficas
            $table->index(['polif_type', 'polif_id'], 'idx_polif_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_polif');
    }
};