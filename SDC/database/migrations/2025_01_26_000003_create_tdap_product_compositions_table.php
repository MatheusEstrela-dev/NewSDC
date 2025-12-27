<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_product_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_composto_id')->constrained('tdap_products')->cascadeOnDelete();
            $table->foreignId('product_componente_id')->constrained('tdap_products')->cascadeOnDelete();
            $table->decimal('quantidade', 10, 3);
            $table->string('unidade_medida', 20)->default('unidade'); // unidade, kg, litro, etc.
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('product_composto_id');
            $table->index('product_componente_id');
            $table->unique(['product_composto_id', 'product_componente_id'], 'unique_composition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_product_compositions');
    }
};
