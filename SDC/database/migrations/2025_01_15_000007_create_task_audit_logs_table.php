<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Trilha de Auditoria (Audit Log)
     *
     * Registro imutável de "Quem mudou o quê e quando" conforme papiro
     */
    public function up(): void
    {
        if (Schema::hasTable('task_audit_logs')) {
            return;
        }

        Schema::create('task_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('acao', [
                'created',
                'updated',
                'deleted',
                'status_changed',
                'assigned',
                'commented',
                'approved',
                'rejected',
            ])->index();

            $table->string('campo', 100)->nullable()
                ->comment('Campo que foi alterado');

            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->json('metadata')->nullable()
                ->comment('Dados adicionais da ação');

            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['acao', 'created_at']);

            // Garantir imutabilidade (no Laravel, via Observer)
            // Sem updated_at e deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_audit_logs');
    }
};
