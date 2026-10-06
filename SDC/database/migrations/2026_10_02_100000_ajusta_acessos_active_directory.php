<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leva bancos que ja rodaram 2026_09_23_000002 ao schema que a instalacao limpa
 * produz (espelho do AD, operacoes, entregas de senha, sincronizacao e a fila
 * diretorio). Em instalacao limpa tudo aqui e no-op. A fonte do schema e a
 * principal; os creates abaixo a repetem, como o precedente 2026_09_28_100000.
 *
 * Os slugs acessos.diretorio.view e acessos.diretorio.reset nao sao criados
 * aqui: estao no config/permissions.php, e o SincronizaPermissoesAposMigrations
 * os cria e concede no MigrationsEnded deste mesmo `migrate`, por serem novos
 * na execucao. Migration antiga nao chama codigo vivo da aplicacao.
 *
 * acessos.diretorio.manage ja existia (grupo Cadastros, sem cargo). O modo
 * somente novas do listener nao concede permissao antiga, entao o par novo
 * manager/analyst -> manage e concedido aqui, uma vez, e a linha muda para o
 * grupo Diretorio quando ainda tem os valores gerados (nunca o que foi editado
 * a mao). Em instalacao limpa a permissao ainda nao existe e nada acontece.
 */
return new class extends Migration
{
    private const PERMISSAO_MANAGE = 'acessos.diretorio.manage';

    private const CARGOS_MANAGE = ['manager', 'analyst'];

    private const GRUPO_ANTIGO = 'cadastros';

    private const DESCRICAO_ANTIGA = 'Directory Cadastros (ACESSOS)';

    public function up(): void
    {
        $this->colunasEspelho();
        $this->operacoes();
        $this->entregasSenha();
        $this->sincronizacoes();
        $this->divergencias();
        $this->filaDiretorio();
        $this->permissaoManage();
    }

    public function down(): void
    {
        // Sem volta: a principal ja descreve o estado final e o down dela derruba tudo.
    }

    private function colunasEspelho(): void
    {
        $colunas = [
            'object_guid' => static fn (Blueprint $t) => $t->uuid('object_guid')->nullable()->unique(),
            'dn_ad' => static fn (Blueprint $t) => $t->string('dn_ad', 500)->nullable(),
            'conta_ativa_ad' => static fn (Blueprint $t) => $t->boolean('conta_ativa_ad')->nullable(),
            'bloqueada_ad' => static fn (Blueprint $t) => $t->boolean('bloqueada_ad')->nullable(),
            'troca_senha_pendente_ad' => static fn (Blueprint $t) => $t->boolean('troca_senha_pendente_ad')->nullable(),
            'ad_sincronizado_em' => static fn (Blueprint $t) => $t->timestamp('ad_sincronizado_em')->nullable(),
        ];
        $faltantes = array_filter(
            $colunas,
            static fn (string $coluna): bool => ! Schema::hasColumn('acessos_cadastros', $coluna),
            ARRAY_FILTER_USE_KEY,
        );

        if ($faltantes !== []) {
            Schema::table('acessos_cadastros', function (Blueprint $table) use ($faltantes): void {
                foreach ($faltantes as $definir) {
                    $definir($table);
                }
            });
        }

        DB::statement('CREATE INDEX IF NOT EXISTS acessos_cadastros_status_ad_index ON acessos_cadastros (status_ad)');
    }

    private function operacoes(): void
    {
        if (! Schema::hasTable('acessos_operacoes_ad')) {
            Schema::create('acessos_operacoes_ad', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('chave_idempotencia', 120)->unique();
                $table->foreignId('cadastro_id')->nullable()->constrained('acessos_cadastros')->nullOnDelete();
                $table->string('login_ad', 100);
                $table->uuid('object_guid')->nullable();
                $table->string('acao', 30);
                $table->string('origem', 20);
                $table->unsignedBigInteger('origem_id')->nullable();
                $table->foreignId('solicitado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('motivo', 500)->nullable();
                $table->string('estado', 20)->default('solicitado');
                $table->string('codigo_erro', 40)->nullable();
                $table->unsignedSmallInteger('tentativas')->default(0);
                $table->jsonb('resultado')->nullable();
                $table->timestamp('enviado_em')->nullable();
                $table->timestamp('concluido_em')->nullable();
                $table->timestamps();
                $table->index(['estado', 'created_at']);
                $table->index(['cadastro_id', 'created_at']);
                $table->index(['origem', 'origem_id']);
            });
        }

        DB::statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS acessos_operacoes_ad_em_voo_unique ON acessos_operacoes_ad (lower(login_ad), acao)
             WHERE estado IN ('solicitado', 'enviado')"
        );
    }

    private function entregasSenha(): void
    {
        if (Schema::hasTable('acessos_entregas_senha')) {
            return;
        }

        Schema::create('acessos_entregas_senha', function (Blueprint $table): void {
            $table->foreignUuid('operacao_id')->primary()->constrained('acessos_operacoes_ad')->cascadeOnDelete();
            $table->foreignId('destinatario_id')->constrained('users')->cascadeOnDelete();
            $table->text('senha_cifrada');
            $table->timestamp('expira_em')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function sincronizacoes(): void
    {
        if (Schema::hasTable('acessos_sincronizacoes_ad')) {
            return;
        }

        Schema::create('acessos_sincronizacoes_ad', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('disparada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('simulacao')->default(false);
            $table->string('estado', 20)->default('solicitada');
            $table->string('codigo_erro', 40)->nullable();
            $table->jsonb('totais')->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });
    }

    private function divergencias(): void
    {
        if (Schema::hasTable('acessos_divergencias_ad')) {
            return;
        }

        Schema::create('acessos_divergencias_ad', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('sincronizacao_id')->constrained('acessos_sincronizacoes_ad')->cascadeOnDelete();
            $table->foreignId('cadastro_id')->nullable()->constrained('acessos_cadastros')->cascadeOnDelete();
            $table->uuid('object_guid')->nullable();
            $table->string('login_ad', 100)->nullable();
            $table->string('tipo', 40);
            $table->jsonb('detalhe');
            $table->timestamp('created_at')->nullable();
            $table->index(['sincronizacao_id', 'tipo']);
        });
    }

    private function filaDiretorio(): void
    {
        if (Schema::hasTable('acessos_fila_diretorio')) {
            return;
        }

        Schema::create('acessos_fila_diretorio', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    private function permissaoManage(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $guard = (string) config('permissions.guard', 'web');
        $permissao = DB::table('permissions')
            ->where('name', self::PERMISSAO_MANAGE)
            ->where('guard_name', $guard)
            ->whereNull('deleted_at')
            ->first(['id', 'group', 'description']);
        if ($permissao === null) {
            return;
        }

        $ajustes = array_filter([
            'group' => $permissao->group === self::GRUPO_ANTIGO ? 'diretorio' : null,
            'description' => $permissao->description === self::DESCRICAO_ANTIGA ? 'Gerenciar Diretorio (ACESSOS)' : null,
        ]);
        if ($ajustes !== []) {
            DB::table('permissions')->where('id', $permissao->id)->update([...$ajustes, 'updated_at' => now()]);
        }

        $cargos = DB::table('roles')
            ->where('guard_name', $guard)
            ->whereNull('deleted_at')
            ->whereIn('slug', self::CARGOS_MANAGE)
            ->pluck('id');
        DB::table('role_has_permissions')->insertOrIgnore($cargos->map(static fn (mixed $id): array => [
            'permission_id' => (int) $permissao->id,
            'role_id' => (int) $id,
        ])->all());
    }
};
