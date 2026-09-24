<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leva bancos que ja rodaram as migrations principais ao mesmo schema que uma
 * instalacao limpa produz. Em instalacao limpa tudo aqui e no-op: as principais
 * ja criam o estado final.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'criado_por_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->foreignId('criado_por_id')->nullable()->after('atribuido_para_id')
                    ->constrained('users')->nullOnDelete();
                $table->index(['criado_por_id', 'created_at']);
            });
        }

        if (! Schema::hasColumn('demanda_assuntos', 'form_automacao')) {
            Schema::table('demanda_assuntos', function (Blueprint $table): void {
                $table->jsonb('form_automacao')->nullable()->after('campos_dinamicos');
            });
        }

        DB::statement('ALTER TABLE task_audit_logs DROP CONSTRAINT IF EXISTS task_audit_logs_acao_check');
        DB::statement('ALTER TABLE task_audit_logs ALTER COLUMN acao TYPE varchar(40)');
    }

    public function down(): void
    {
        // Sem volta para o enum: registros com acoes novas violariam o check.
    }
};
