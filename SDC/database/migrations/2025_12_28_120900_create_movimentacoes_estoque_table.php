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
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->unsignedBigInteger('estoque_id');

            // Tipo e Quantidade
            $table->string('tipo_movimentacao'); // ENTRADA, SAIDA, AJUSTE, PERDA
            $table->decimal('quantidade', 10, 2);

            // Data
            $table->date('data_movimentacao');

            // Origem/Destino
            $table->string('origem_destino');
            $table->unsignedBigInteger('doacao_id')->nullable();
            $table->unsignedBigInteger('auxilio_id')->nullable();
            $table->unsignedBigInteger('abrigo_id')->nullable();

            // Responsável
            $table->string('responsavel');

            // Observações
            $table->text('observacoes')->nullable();

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('estoque_id')
                  ->references('id')
                  ->on('estoques')
                  ->onDelete('restrict');

            // Índices
            $table->index('estoque_id');
            $table->index('tipo_movimentacao');
            $table->index('data_movimentacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};
