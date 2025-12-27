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
        Schema::create('processo_prejuizos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->onDelete('cascade');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->onDelete('set null');
            $table->string('tipo');
            $table->string('setor')->nullable();
            $table->decimal('area_afetada', 15, 2)->nullable();
            $table->decimal('valor', 15, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processo_prejuizos');
    }
};
