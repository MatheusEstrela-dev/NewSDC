<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_products', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('tipo', 50); // Enum: ProductType
            $table->boolean('eh_composto')->default(false);
            $table->decimal('volume_unitario_m3', 10, 4)->default(0);
            $table->decimal('peso_unitario_kg', 10, 3)->default(0);
            $table->integer('estoque_minimo')->default(0);
            $table->integer('estoque_maximo')->nullable();
            $table->string('estrategia_armazenamento', 20)->nullable(); // Enum: StorageStrategy
            $table->string('grupo_risco', 50)->default('GERAL'); // ALIMENTO, QUIMICO, GERAL
            $table->integer('dias_alerta_validade')->default(30);
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('tipo');
            $table->index('grupo_risco');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_products');
    }
};
