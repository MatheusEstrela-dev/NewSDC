<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro exactly-once do lado do sdc para a caixa de entrada do sdc_entrada.
 *
 * Fica no sdc DE PROPOSITO (e sem FK para entrada.mensagens, que mora em outro
 * banco (sdc_entrada, no cluster do sdc, mas sem FK entre bancos)): os dois bancos nao compartilham transacao, entao o sdc e o juiz.
 * Mesmo padrao de processed_events: o AplicarEntradaJob (ProcessadorDeEntrada)
 * insere a mensagem_id aqui (ON CONFLICT DO NOTHING) na mesma transacao do
 * efeito de dominio, depois grava resultado/recurso. Se a linha ja existe, o
 * efeito nao se repete: a entrada so copia o resultado gravado (caso da
 * marcacao de aplicada no sdc_entrada ter falhado depois do commit).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integracao_aplicacoes')) {
            return;
        }

        Schema::create('integracao_aplicacoes', function (Blueprint $table): void {
            $table->uuid('mensagem_id')->primary();
            $table->text('operacao');
            $table->text('recurso_tipo')->nullable();
            $table->text('recurso_id')->nullable();
            $table->jsonb('resultado')->nullable();
            $table->timestampTz('aplicado_em')->useCurrent();
            $table->index(['recurso_tipo', 'recurso_id'], 'idx_integracao_aplicacoes_recurso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integracao_aplicacoes');
    }
};
