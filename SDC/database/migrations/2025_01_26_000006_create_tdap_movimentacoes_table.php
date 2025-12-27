<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_movimentacao', 50)->unique();
            $table->string('tipo', 50); // Enum: MovimentacaoType
            $table->foreignId('product_id')->constrained('tdap_products');
            $table->foreignId('lote_id')->nullable()->constrained('tdap_product_lotes');
            $table->integer('quantidade');
            $table->datetime('data_movimentacao');
            $table->string('origem')->nullable();
            $table->string('destino')->nullable();
            $table->foreignId('solicitante_id')->nullable()->constrained('users');
            $table->foreignId('responsavel_id')->nullable()->constrained('users');
            $table->string('documento_referencia')->nullable(); // NF, OC, etc.
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_movimentacao');
            $table->index('tipo');
            $table->index('product_id');
            $table->index('lote_id');
            $table->index('data_movimentacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_movimentacoes');
    }
};
