<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_recebimento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recebimento_id')->constrained('tdap_recebimentos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('tdap_products');
            $table->integer('quantidade_nota');
            $table->integer('quantidade_conferida');
            $table->string('numero_lote', 100)->nullable();
            $table->date('data_fabricacao')->nullable();
            $table->date('data_validade')->nullable();
            $table->boolean('tem_avaria')->default(false);
            $table->string('tipo_avaria')->nullable(); // molhado, rasgado, vazamento, etc.
            $table->integer('quantidade_avariada')->default(0);
            $table->string('foto_avaria')->nullable(); // Path para foto da avaria
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('recebimento_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_recebimento_itens');
    }
};
