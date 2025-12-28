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
        Schema::create('movimentacoes_financeiras', function (Blueprint $table) {
            $table->id();

            // Tipo e Categoria
            $table->string('tipo_movimentacao'); // ENTRADA, SAIDA
            $table->string('categoria'); // DOACAO, COMPRA_MATERIAL, AUXILIO_FINANCEIRO, DESPESA_OPERACIONAL, OUTRA

            // Descrição e Valor
            $table->string('descricao');
            $table->decimal('valor', 10, 2);

            // Data
            $table->date('data_movimentacao');

            // Relacionamentos
            $table->unsignedBigInteger('doacao_id')->nullable();
            $table->unsignedBigInteger('auxilio_id')->nullable();

            // Forma de Pagamento
            $table->string('forma_pagamento')->nullable();
            $table->string('comprovante_path')->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tipo_movimentacao');
            $table->index('categoria');
            $table->index('data_movimentacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_financeiras');
    }
};
