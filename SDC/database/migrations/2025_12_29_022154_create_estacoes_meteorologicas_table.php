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
        Schema::create('estacoes_meteorologicas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->index();
            $table->string('nome');
            $table->string('municipio');
            $table->string('uf', 2)->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->string('tipo')->default('automatica');
            $table->string('status')->default('ativo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacoes_meteorologicas');
    }
};
