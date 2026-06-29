<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro mestre de comunidades por municipio (reutilizavel em qualquer PMDA).
 * Espelha o legado pip_comunidade: comunidades aprovadas pela CEDEC ficam aqui
 * e passam a aparecer no seletor "Adicionar Comunidade" da aba de distribuicao.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipio_id')->index();
            $table->string('nome', 150);
            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipio_id')->references('id')->on('municipios')->cascadeOnDelete();
            $table->unique(['municipio_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunidades');
    }
};
