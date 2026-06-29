<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitacoes de inclusao de comunidade. O municipio pede o cadastro de uma
 * comunidade que ainda nao existe no registro mestre; a CEDEC aprova (promove
 * para a tabela comunidades) ou rejeita (com motivo).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_comunidade_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipio_id')->index();
            $table->foreignId('pmda_plano_id')->nullable()->constrained('pmda_planos')->nullOnDelete();
            $table->string('nome', 150);
            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();
            // PENDENTE | APROVADA | REJEITADA
            $table->string('status', 20)->default('PENDENTE')->index();
            // Preenchido na aprovacao: comunidade mestre gerada.
            $table->unsignedBigInteger('comunidade_id')->nullable()->index();
            $table->unsignedBigInteger('solicitado_por')->nullable();
            $table->unsignedBigInteger('analisado_por')->nullable();
            $table->timestamp('analisado_em')->nullable();
            $table->string('motivo_rejeicao', 255)->nullable();
            $table->timestamps();

            $table->foreign('municipio_id')->references('id')->on('municipios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_comunidade_solicitacoes');
    }
};
