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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();

            // Produto
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->string('descricao');
            $table->string('categoria'); // ALIMENTO, HIGIENE, VESTUARIO, MEDICAMENTO, LIMPEZA, OUTRO

            // Quantidade
            $table->decimal('quantidade_atual', 10, 2)->default(0);
            $table->decimal('quantidade_minima', 10, 2)->default(0);
            $table->string('unidade_medida', 20);

            // Localização
            $table->string('localizacao')->nullable();

            // Valor
            $table->decimal('valor_unitario_medio', 10, 2)->nullable();

            // Inventário
            $table->date('ultimo_inventario')->nullable();

            $table->timestamps();

            // Índices
            $table->index('categoria');
            $table->index('quantidade_atual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
