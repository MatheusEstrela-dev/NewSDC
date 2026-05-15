<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sagas: fluxos de orquestracao que aguardam multiplos eventos para finalizar.
 *
 * Exemplo: EncerramentoSaga espera (ultima viagem validada) + (NF emitida)
 * antes de transitar o ProcessoTdap para LIQUIDACAO_PENDENTE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_sagas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('saga_class', 200)->index();
            $table->uuid('correlation_id')->index()
                ->comment('Geralmente o aggregate_id do agregado raiz da saga');
            $table->string('estado_saga', 30)->default('iniciada');
            $table->jsonb('estado_acumulado');
            $table->timestampTz('iniciada_em');
            $table->timestampTz('completada_em')->nullable();
            $table->timestamps();

            $table->unique(['saga_class', 'correlation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_sagas');
    }
};
