<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Tabela Task (Table Inheritance Pattern)
     *
     * Tabela mestre que centraliza campos comuns de todas as demandas
     * (Incidentes, Solicitações, Mudanças, Problemas)
     *
     * Baseado no papiro task01.md e padrão ITIL/ITSM
     */
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            return;
        }

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Identificação e Tipo
            $table->string('protocolo', 50)->unique()->index();
            $table->enum('tipo', ['incidente', 'solicitacao', 'mudanca', 'problema'])
                ->index()
                ->comment('Tipo de task (Table Inheritance discriminator)');

            // Informações Básicas
            $table->string('titulo', 255);
            $table->text('descricao')->nullable();

            // State Machine: Status
            $table->enum('status', [
                'aberta',
                'em_analise',
                'em_progresso',
                'aguardando_terceiros',
                'resolvida',
                'fechada',
                'cancelada',
            ])->default('aberta')->index();

            // Matriz de Prioridade
            $table->enum('impacto', ['alto', 'medio', 'baixo'])->default('medio');
            $table->enum('urgencia', ['alta', 'media', 'baixa'])->default('media');
            $table->integer('prioridade')->default(3)
                ->comment('1=Crítica, 2=Alta, 3=Média, 4=Baixa, 5=Planejada')
                ->index();

            // Relacionamentos de Pessoas
            $table->foreignId('solicitante_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Quem solicitou/reportou');

            $table->foreignId('atribuido_para_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Responsável atual');

            $table->foreignId('grupo_id')
                ->nullable()
                ->comment('Grupo/Equipe responsável (FK futura para groups table)');

            // Categoria e Roteamento
            $table->string('categoria', 100)->nullable()->index();
            $table->string('subcategoria', 100)->nullable();

            // SLA (Service Level Agreement)
            $table->timestamp('prazo_primeira_resposta')->nullable();
            $table->timestamp('primeira_resposta_em')->nullable();
            $table->timestamp('prazo_resolucao')->nullable();
            $table->timestamp('resolvido_em')->nullable();

            $table->boolean('sla_primeira_resposta_violado')->default(false)->index();
            $table->boolean('sla_resolucao_violado')->default(false)->index();

            // Campos Dinâmicos (JSON Schema)
            $table->json('campos_customizados')->nullable()
                ->comment('Formulários dinâmicos baseados no catálogo de serviços');

            // Métricas de Tempo
            $table->integer('tempo_em_aberta')->default(0)
                ->comment('Minutos em status aberta');
            $table->integer('tempo_em_progresso')->default(0)
                ->comment('Minutos em status em_progresso');
            $table->integer('tempo_total_resolucao')->nullable()
                ->comment('Minutos totais até resolução');

            // Metadados
            $table->timestamps();
            $table->softDeletes();

            // Índices Compostos para Performance
            $table->index(['status', 'prioridade']);
            $table->index(['tipo', 'status']);
            $table->index(['atribuido_para_id', 'status']);
            $table->index(['solicitante_id', 'created_at']);
            $table->index(['categoria', 'status']);

            // TODO: Full-text search (adicionar manualmente se necessário)
            // DB::statement('ALTER TABLE tasks ADD FULLTEXT idx_tasks_titulo_fulltext(titulo)');
            // DB::statement('ALTER TABLE tasks ADD FULLTEXT idx_tasks_descricao_fulltext(descricao)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
