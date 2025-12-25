<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Definições de SLA (Service Level Agreement)
     *
     * Motor de SLA conforme papiro task01.md
     * Calcula "Tempo de Negócio" excluindo fins de semana e feriados
     */
    public function up(): void
    {
        if (Schema::hasTable('task_sla_definitions')) {
            return;
        }

        Schema::create('task_sla_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('nome', 100)->unique();
            $table->text('descricao')->nullable();

            // Condições de Aplicação
            $table->enum('tipo_task', ['incidente', 'solicitacao', 'mudanca', 'problema'])
                ->nullable()
                ->index();
            $table->integer('prioridade')->nullable()
                ->index()
                ->comment('1-5, null=todas');
            $table->string('categoria')->nullable();

            // Tempos (em minutos de tempo útil)
            $table->integer('tempo_primeira_resposta')
                ->comment('Minutos úteis para primeira resposta');
            $table->integer('tempo_resolucao')
                ->comment('Minutos úteis para resolução');

            // Horário de Funcionamento (Tempo de Negócio)
            $table->time('horario_inicio')->default('08:00:00');
            $table->time('horario_fim')->default('18:00:00');

            // Dias de Funcionamento (JSON array de números 0-6, onde 0=Domingo)
            $table->json('dias_funcionamento')
                ->comment('Array de dias úteis: [1,2,3,4,5] = Segunda-Sexta');

            // Feriados (JSON array de datas no formato YYYY-MM-DD)
            $table->json('feriados')->nullable()
                ->comment('Array de datas: ["2025-01-01", "2025-12-25"]');

            // Alertas de Violação
            $table->json('alertas')
                ->comment('Percentuais para disparo de alertas: [50, 75, 90]');

            $table->boolean('ativo')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sla_definitions');
    }
};
