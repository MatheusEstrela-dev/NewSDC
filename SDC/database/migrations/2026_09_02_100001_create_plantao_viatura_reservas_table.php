<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viatura_reservas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('viatura_id')
                ->constrained('plantao_viaturas')->cascadeOnDelete();

            // restrictOnDelete igual ao condutor da movimentacao: a reserva e
            // parte da trilha de quem estava com a chave, entao apagar o usuario
            // nao pode apagar a resposta.
            $table->foreignId('agente_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('agente_nome');

            $table->dateTime('inicio_previsto');
            $table->dateTime('fim_previsto');

            $table->string('status', 20)->default('AGENDADA');

            // Pre-preenchem o formulario de saida no check-in. Mesmos limites
            // de plantao_viatura_movimentacoes: o valor e copiado para la.
            $table->string('destino', 160)->nullable();
            $table->string('motivo', 160)->nullable();

            // Nulo ate o check-in. E o elo que responde "esta reserva virou
            // qual saida"; a movimentacao continua sendo o fato consumado, e a
            // reserva apenas a intencao que a originou.
            $table->foreignId('movimentacao_id')->nullable()
                ->constrained('plantao_viatura_movimentacoes')->nullOnDelete();

            $table->dateTime('checkin_em')->nullable();
            $table->dateTime('checkout_em')->nullable();

            $table->dateTime('cancelada_em')->nullable();
            $table->string('cancelamento_motivo', 200)->nullable();

            // Quem cancelou, que nem sempre e o dono: supervisao derruba
            // reserva alheia para liberar a viatura. Sem isto, a reserva
            // sumiria da agenda sem responder quem a tirou de la.
            $table->foreignId('cancelada_por_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('cancelada_por_nome')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Sustenta a checagem de conflito de agenda, que filtra por viatura
            // e status antes de comparar as janelas de horario.
            $table->index(['viatura_id', 'status']);

            // Sustenta o scan: dado o agente, achar a reserva vigente dele.
            $table->index(['agente_id', 'status']);

            // Sustenta plantao:expirar-reservas, que varre por janela vencida.
            $table->index(['status', 'fim_previsto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viatura_reservas');
    }
};
