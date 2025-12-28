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
        Schema::create('itens_doacao', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->unsignedBigInteger('doacao_id');
            $table->unsignedBigInteger('produto_id')->nullable();

            // Dados do Item
            $table->string('descricao');
            $table->string('categoria'); // ALIMENTO, HIGIENE, VESTUARIO, MEDICAMENTO, LIMPEZA, OUTRO
            $table->decimal('quantidade', 10, 2);
            $table->string('unidade_medida', 20);
            $table->decimal('valor_unitario_estimado', 10, 2)->nullable();

            // Validade
            $table->date('data_validade')->nullable();
            $table->string('lote')->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('doacao_id')
                  ->references('id')
                  ->on('doacoes')
                  ->onDelete('cascade');

            // Índices
            $table->index('doacao_id');
            $table->index('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_doacao');
    }
};
