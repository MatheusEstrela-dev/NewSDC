<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fonte unica dos horarios de turno praticados.
 *
 * Substitui o enum PHP PeriodoPlantao, que fixava tres horarios no codigo e
 * exigia deploy para cada horario novo. A coluna plantoes.periodo continua
 * varchar e passa a guardar o `codigo` daqui -- nao ha CHECK constraint sobre
 * ela, entao a tabela ja existente aceita os valores novos sem alteracao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_tipos_turno', function (Blueprint $table) {
            $table->id();

            // Casa com plantoes.periodo. Imutavel apos o primeiro uso: mudar o
            // codigo orfanaria os turnos ja gravados, que guardam a string.
            $table->string('codigo', 30)->unique();
            $table->string('nome', 60);

            // Nulas de proposito no EXTRAORDINARIO, que nao tem hora definida.
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();

            // Turno que termina no dia seguinte (16h-02h, 20h-08h). Sem isto a
            // deteccao de sobreposicao calcularia intervalo negativo.
            $table->boolean('vira_dia')->default(false);

            // Turno sem hora fixa nao pode ser escalado -- so aberto na hora.
            $table->boolean('escalavel')->default(true);

            // Hex com #. Vai cru para o calendario; nao vira classe Tailwind,
            // que nao escaneia PHP nem valores vindos do banco.
            $table->string('cor', 7)->default('#64748b');

            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);

            $table->timestamps();

            $table->index(['ativo', 'ordem']);
            $table->index('escalavel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_tipos_turno');
    }
};
