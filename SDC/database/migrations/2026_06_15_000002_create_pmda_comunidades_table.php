<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_comunidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_plano_id')->constrained('pmda_planos')->cascadeOnDelete();
            $table->unsignedBigInteger('comunidade_id')->nullable()->index();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('ponto_id')->nullable()->index(); // vinculo de captacao amarrado na Fase 3
            $table->string('nome', 150)->nullable();
            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();
            $table->decimal('trecho_pav', 8, 2)->nullable();
            $table->decimal('trecho_n_pav', 8, 2)->nullable();
            $table->unsignedInteger('pop_atendida')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipio_id')->references('id')->on('municipios')->nullOnDelete();
            $table->unique(['pmda_plano_id', 'comunidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_comunidades');
    }
};
