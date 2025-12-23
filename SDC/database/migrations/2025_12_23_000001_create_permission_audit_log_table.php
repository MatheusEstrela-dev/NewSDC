<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Essa migration pode ter falhado após criar a tabela (ex.: tentativa de CHECK inválido em MySQL).
        // Para permitir re-execução sem quebrar, tornamos idempotente.
        if (Schema::hasTable('permission_audit_log')) {
            return;
        }

        Schema::create('permission_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('action', 50);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_pal_user_id');
            $table->index('action', 'idx_pal_action');
            $table->index(['entity_type', 'entity_id'], 'idx_pal_entity');
            $table->index('created_at', 'idx_pal_created_at');
        });

        // OBS (MySQL 8+): CHECK constraints NÃO permitem funções não determinísticas como NOW().
        // Isso derruba a migration e impede criar as tabelas de permissionamento (model_has_roles, etc.).
        // A imutabilidade deve ser garantida pela aplicação (Policy/Observer) ou por trigger (se necessário).
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_audit_log');
    }
};
