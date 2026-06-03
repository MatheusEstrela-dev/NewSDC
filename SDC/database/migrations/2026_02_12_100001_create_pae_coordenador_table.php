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
        if (!Schema::hasTable('pae_coordenador')) {
            Schema::create('pae_coordenador', function (Blueprint $table) {
                // id_coordenador bigint unsigned NOT NULL AUTO_INCREMENT
                $table->bigIncrements('id_coordenador');

                $table->string('nome', 255);
                $table->string('telefone', 50)->nullable();
                
                // email UNIQUE e com índice nomeado conforme seu SQL
                $table->string('email', 255)->nullable()->unique('email');

                // created_at e updated_at
                $table->timestamps();

                // Índice adicional para o email (seguindo seu KEY `idx_coordenador_email`)
                $table->index('email', 'idx_coordenador_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_coordenador');
    }
};