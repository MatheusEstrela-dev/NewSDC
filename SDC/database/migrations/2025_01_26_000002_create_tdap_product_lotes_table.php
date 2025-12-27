<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_product_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('tdap_products')->cascadeOnDelete();
            $table->string('numero_lote', 100);
            $table->datetime('data_entrada');
            $table->date('data_fabricacao')->nullable();
            $table->date('data_validade')->nullable();
            $table->integer('quantidade_inicial');
            $table->integer('quantidade_atual');
            $table->string('localizacao')->nullable(); // Ex: Prateleira A-12, Pallet 5
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('numero_lote');
            $table->index('data_validade');
            $table->index(['product_id', 'quantidade_atual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_product_lotes');
    }
};
