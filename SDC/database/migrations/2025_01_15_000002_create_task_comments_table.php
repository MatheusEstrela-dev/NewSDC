<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Comentários e Timeline das Tasks
     *
     * Armazena histórico de interações, comentários e atualizações
     */
    public function up(): void
    {
        if (Schema::hasTable('task_comments')) {
            return;
        }

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('tipo', ['comentario', 'atualizacao', 'sistema'])
                ->default('comentario')
                ->comment('comentario=usuário, atualizacao=mudança de campo, sistema=automático');

            $table->text('conteudo');

            $table->boolean('interno')->default(false)
                ->comment('Visível apenas para agentes');

            $table->boolean('enviado_email')->default(false);

            // Metadados da atualização (para tipo=atualizacao)
            $table->json('metadata')->nullable()
                ->comment('Ex: {"campo": "status", "de": "aberta", "para": "em_progresso"}');

            $table->timestamps();

            // Índices
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
