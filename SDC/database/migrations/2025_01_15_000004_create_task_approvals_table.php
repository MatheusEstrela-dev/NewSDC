<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Aprovações de Mudanças (Change Advisory Board - CAB)
     *
     * Para implementar o fluxo de aprovação de mudanças conforme papiro
     */
    public function up(): void
    {
        if (Schema::hasTable('task_approvals')) {
            return;
        }

        Schema::create('task_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('aprovador_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', ['pendente', 'aprovado', 'rejeitado', 'cancelado'])
                ->default('pendente')
                ->index();

            $table->text('comentario')->nullable();

            $table->timestamp('respondido_em')->nullable();

            $table->integer('ordem')->default(1)
                ->comment('Para aprovações sequenciais');

            $table->boolean('obrigatorio')->default(true);

            $table->timestamps();

            // Índices
            $table->index(['task_id', 'status']);
            $table->index(['aprovador_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_approvals');
    }
};
