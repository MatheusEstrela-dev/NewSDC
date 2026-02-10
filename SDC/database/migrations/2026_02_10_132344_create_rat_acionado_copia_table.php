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
        Schema::create('rat_orgao_acionado_copia', function (Blueprint $table) {
            $table->id();

            // Descrição com comentário e índice
            $table->string('descricao', 255)
                  ->comment('Descrição do órgão acionado');

            // Auditoria
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();

            // Datas (Inclusão, Edição e Exclusão Lógica)
            $table->timestamps();
            $table->softDeletes();

            // Índice para busca rápida pela descrição
            $table->index('descricao', 'idx_rat_orgao_acionado_descricao');

            // Configurações de Banco
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
        Schema::dropIfExists('rat_orgao_acionado_copia');
    }
};