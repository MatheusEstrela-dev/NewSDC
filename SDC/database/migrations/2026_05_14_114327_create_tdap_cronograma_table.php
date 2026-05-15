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
        Schema::create('tdap_cronograma', function (Blueprint $table) {

            $table->id();

            $table->string('numero', 6)
                ->comment('Número do Cronograma');

            $table->string('empenho', 10)
                ->comment('Número Nota de Empenho');

            $table->timestamp('dt_inicio')
                ->nullable()
                ->comment('Data Inicial');

            $table->timestamp('dt_final')
                ->nullable()
                ->comment('Data Final');

            $table->integer('prestador_id')
                ->comment('Identificador Prestador');

            $table->integer('municipio_id')
                ->comment('Identificador Municipio');

            $table->integer('pmda_id')
                ->comment('Identificador Pmda');

            $table->integer('ata_id')
                ->comment('Ata de Registro');

            $table->boolean('viagem')
                ->default(true);

            $table->float('consumo_diario')
                ->nullable();

            $table->unsignedInteger('dias')
                ->nullable();

            $table->string('justificativa')
                ->nullable();

            $table->string('nota_empenho')
                ->nullable();

            $table->unsignedInteger('ponto_captacao_id');

            $table->unsignedInteger('user_id')
                ->default(1);

            $table->text('stored_caminhoes');

            $table->text('stored_pmda_ponto');

            $table->text('stored_municipio');

            $table->text('stored_prestador');

            $table->unsignedInteger('lote_id')
                ->nullable();

            $table->text('stored_lote')
                ->nullable();

            $table->boolean('ativo')
                ->default(false);

            $table->timestamp('dt_inicio_prorrogacao')
                ->nullable();

            $table->timestamp('dt_final_prorrogacao')
                ->nullable();

            $table->text('observacao')
                ->nullable();

            $table->decimal('fator', 10, 2)
                ->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_cronograma');
    }
};