<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Instâncias de SLA (tempo correndo em um ticket)
     *
     * Cada task tem uma instância de SLA que rastreia o tempo decorrido
     */
    public function up(): void
    {
        if (Schema::hasTable('task_sla_instances')) {
            return;
        }

        Schema::create('task_sla_instances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('sla_definition_id')
                ->constrained('task_sla_definitions')
                ->cascadeOnDelete();

            // Primeira Resposta
            $table->timestamp('primeira_resposta_inicio')->nullable();
            $table->timestamp('primeira_resposta_prazo')->nullable();
            $table->timestamp('primeira_resposta_atingido')->nullable();
            $table->integer('primeira_resposta_tempo_util_decorrido')->default(0)
                ->comment('Minutos úteis decorridos');
            $table->boolean('primeira_resposta_violado')->default(false)->index();
            $table->decimal('primeira_resposta_percentual', 5, 2)->default(0);

            // Resolução
            $table->timestamp('resolucao_inicio')->nullable();
            $table->timestamp('resolucao_prazo')->nullable();
            $table->timestamp('resolucao_atingido')->nullable();
            $table->integer('resolucao_tempo_util_decorrido')->default(0)
                ->comment('Minutos úteis decorridos');
            $table->boolean('resolucao_violado')->default(false)->index();
            $table->decimal('resolucao_percentual', 5, 2)->default(0);

            // Pausas (quando status = aguardando_terceiros)
            $table->timestamp('pausado_em')->nullable();
            $table->integer('tempo_pausado_total')->default(0)
                ->comment('Minutos totais em pausa');

            // Última atualização do cálculo
            $table->timestamp('ultima_atualizacao')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['task_id', 'primeira_resposta_violado']);
            $table->index(['task_id', 'resolucao_violado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sla_instances');
    }
};
