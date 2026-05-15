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
        Schema::create('tdap_lotes', function (Blueprint $table) {

            $table->id();

            $table->integer('ata_id')
                ->comment('Identificador da Ata');

            $table->string('numero', 10)
                ->comment('Número Ata');

            $table->string('nome')
                ->comment('Nome do Lote');

            $table->integer('municipio_id')
                ->comment('Municipio');

            $table->integer('prestador_id')
                ->comment('Prestador');

            $table->string('qtd_agua', 50)
                ->comment('Quantidade de Água M3');

            $table->decimal('valor', 10, 2)
                ->comment('Valor de Água M3');

            $table->string('unidade', 50)
                ->nullable();

            $table->string('contrato', 30)
                ->nullable();

            $table->timestamps();

            $table->index('ata_id');
            $table->index('municipio_id');
            $table->index('prestador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_lotes');
    }
};