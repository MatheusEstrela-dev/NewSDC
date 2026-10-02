<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leva bancos que ja rodaram 2026_09_23_000001 ao schema que a instalacao limpa
 * produz. Em instalacao limpa tudo aqui e no-op.
 *
 * Os slugs do remanejamento em lote (create, edit, seplag) nao sao criados
 * aqui: estao no config/permissions.php, e o SincronizaPermissoesAposMigrations
 * os cria e concede no MigrationsEnded deste mesmo `migrate`, por serem novos
 * na execucao. Migration antiga nao chama codigo vivo da aplicacao.
 */
return new class extends Migration
{
    private const FK_LOTE = 'inventario_ti_movimentacoes_lote_id_foreign';

    public function up(): void
    {
        if (! Schema::hasTable('inventario_ti_remanejamentos')) {
            Schema::create('inventario_ti_remanejamentos', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('observacao')->nullable();
                $table->string('status', 20)->default('ativo')->index();
                $table->foreignId('demanda_id')->nullable()->unique()->constrained('tasks')->nullOnDelete();
                $table->timestamp('seplag_enviado_em')->nullable();
                $table->foreignId('seplag_enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('seplag_envios')->default(0);
                $table->timestamp('desfeito_em')->nullable();
                $table->foreignId('desfeito_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('inventario_ti_remanejamento_pessoas')) {
            Schema::create('inventario_ti_remanejamento_pessoas', function (Blueprint $table): void {
                $table->id();
                $table->foreignUuid('remanejamento_id')->constrained('inventario_ti_remanejamentos')->cascadeOnDelete();
                $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
                $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
                $table->foreignId('estacao_destino_usuario_anterior_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('estacao_origem_ficou_vazia')->default(false);
                $table->string('condicao_destino', 60)->nullable();
                $table->timestamps();
                $table->unique(['remanejamento_id', 'usuario_id']);
            });
        }

        if (! Schema::hasColumn('inventario_ti_movimentacoes', 'remanejamento_pessoa_id')) {
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->foreignId('remanejamento_pessoa_id')->nullable()->after('lote_id')
                    ->constrained('inventario_ti_remanejamento_pessoas')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('inventario_ti_movimentacoes', 'situacao_origem')) {
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->string('situacao_origem', 30)->nullable()->after('status');
            });
        }

        if (! $this->temConstraint(self::FK_LOTE)) {
            // lote_id existia sem FK; qualquer valor sem cabecalho impediria a FK.
            DB::statement(
                'UPDATE inventario_ti_movimentacoes SET lote_id = NULL
                 WHERE lote_id IS NOT NULL AND lote_id NOT IN (SELECT id FROM inventario_ti_remanejamentos)'
            );
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->foreign('lote_id')->references('id')->on('inventario_ti_remanejamentos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Sem volta: a principal ja descreve o estado final e o down dela derruba tudo.
    }

    private function temConstraint(string $nome): bool
    {
        return (int) DB::selectOne('select count(*) as n from pg_constraint where conname = ?', [$nome])->n > 0;
    }
};
