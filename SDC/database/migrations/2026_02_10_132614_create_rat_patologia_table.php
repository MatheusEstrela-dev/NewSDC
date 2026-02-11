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
        if (Schema::hasTable('rat_patologia')) return;
        Schema::create('rat_patologia', function (Blueprint $table) {
            $table->id();

            // Descrição com comentário e índice para buscas
            $table->string('descricao', 255)
                  ->comment('Descrição da patologia identificada');

            // Colunas de Auditoria
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();

            // Controle de Tempo e Soft Deletes
            $table->timestamps();
            $table->softDeletes();

            // Índice específico solicitado
            $table->index('descricao', 'idx_rat_patologia_descricao');

            // Definições de Engine
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_patologia');
    }
};
