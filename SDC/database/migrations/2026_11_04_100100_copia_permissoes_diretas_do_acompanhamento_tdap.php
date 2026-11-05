<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Completa 2026_10_29_100200 e 2026_10_29_120000: as duas copiaram para os
 * slugs novos so as atribuicoes por PAPEL (role_has_permissions). Quem tinha a
 * permissao de origem atribuida DIRETO ao usuario (model_has_permissions)
 * perdeu o passo correspondente do Acompanhamento. Esta migration copia essas
 * atribuicoes diretas, com o mesmo mapa origem -> slug novo:
 *
 *   tdap.cronogramas.view    -> tdap.acompanhamento.view
 *   tdap.viagens.validar     -> tdap.cronogramas.analisar
 *   tdap.cronogramas.create  -> tdap.cronogramas.enviar
 *   tdap.cronogramas.ativar  -> tdap.cronogramas.encerrar
 *
 * Regras:
 *  - NAO cria nem reativa permissao. Se o slug novo (ou o de origem) estiver
 *    ausente, soft-deleted ou is_active = false, o par e pulado: quem desativou
 *    um slug fez isso de proposito.
 *  - Copia `expires_at` junto (permissao temporaria continua temporaria) e
 *    ignora atribuicao de origem ja vencida.
 *  - Idempotente: insertOrIgnore na PK (permission_id, model_id, model_type);
 *    atribuicao direta que ja exista para o slug novo nao e tocada.
 *
 * down() remove SO o que esta migration inseriu. Para isso cada linha
 * efetivamente inserida (insertOrIgnore devolveu 1) fica registrada em
 * `tdap_permissoes_diretas_copiadas`. Limite conhecido: se, entre o up e o
 * down, alguem revogar e reatribuir a mao a mesma permissao ao mesmo usuario,
 * o down a remove tambem -- o banco nao distingue as duas linhas.
 *
 * Cache do Spatie: limpo no fim, uma vez. Com Octane o PermissionRegistrar
 * tambem guarda as permissoes em memoria em cada worker; depois de aplicar,
 * rodar `php artisan octane:reload` no container.
 */
return new class extends Migration
{
    private const REGISTRO = 'tdap_permissoes_diretas_copiadas';

    /** slug de origem => slug novo */
    private const MAPA = [
        'tdap.cronogramas.view'   => 'tdap.acompanhamento.view',
        'tdap.viagens.validar'    => 'tdap.cronogramas.analisar',
        'tdap.cronogramas.create' => 'tdap.cronogramas.enviar',
        'tdap.cronogramas.ativar' => 'tdap.cronogramas.encerrar',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::REGISTRO)) {
            Schema::create(self::REGISTRO, function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->unsignedBigInteger('origem_permission_id');
                $table->timestamp('copiado_em')->useCurrent();
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        foreach (self::MAPA as $origem => $novo) {
            $origemId = $this->permissaoAtiva($origem);
            $novoId = $this->permissaoAtiva($novo);

            if ($origemId === null || $novoId === null) {
                continue;
            }

            $atribuicoes = DB::table('model_has_permissions')
                ->where('permission_id', $origemId)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get(['model_type', 'model_id', 'expires_at']);

            foreach ($atribuicoes as $a) {
                $inseridas = DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $novoId,
                    'model_type'    => $a->model_type,
                    'model_id'      => $a->model_id,
                    'expires_at'    => $a->expires_at,
                ]);

                if ($inseridas === 1) {
                    DB::table(self::REGISTRO)->insertOrIgnore([
                        'permission_id'        => $novoId,
                        'model_type'           => $a->model_type,
                        'model_id'             => $a->model_id,
                        'origem_permission_id' => $origemId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable(self::REGISTRO)) {
            DB::table('model_has_permissions as m')
                ->whereExists(fn ($q) => $q->from(self::REGISTRO.' as r')
                    ->whereColumn('r.permission_id', 'm.permission_id')
                    ->whereColumn('r.model_id', 'm.model_id')
                    ->whereColumn('r.model_type', 'm.model_type'))
                ->delete();

            Schema::drop(self::REGISTRO);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissaoAtiva(string $slug): ?int
    {
        $id = DB::table('permissions')
            ->where('name', $slug)
            ->where('guard_name', 'web')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->value('id');

        return $id === null ? null : (int) $id;
    }
};
