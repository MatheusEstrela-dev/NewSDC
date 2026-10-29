<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Uma permissao por passo do Acompanhamento.
 *
 * - `tdap.cronogramas.enviar` (enviar para analise) vai para quem cadastra
 *   cronograma (`create`). Antes o passo usava `edit`, que no banco so o
 *   gestor e o admin tinham -- o Analista, que cadastra, nao conseguia enviar.
 * - `tdap.cronogramas.encerrar` vai para quem ja ativa: o encerramento saia
 *   da mesma permissao, agora tem slug proprio para poder ser separado.
 *
 * Mesmo molde de 2026_10_29_100200 (query builder, cache limpo uma vez).
 */
return new class extends Migration
{
    /** slug novo => [descricao, slug de origem das atribuicoes] */
    private const SLUGS = [
        'tdap.cronogramas.enviar'   => ['Tdap - Cronogramas - Enviar para analise', 'tdap.cronogramas.create'],
        'tdap.cronogramas.encerrar' => ['Tdap - Cronogramas - Encerrar', 'tdap.cronogramas.ativar'],
    ];

    public function up(): void
    {
        $agora = now();

        foreach (self::SLUGS as $slug => [$descricao, $origem]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $slug, 'guard_name' => 'web'],
                [
                    'slug'         => $slug,
                    'description'  => $descricao,
                    'group'        => 'cronogramas',
                    'module'       => 'tdap',
                    'is_active'    => true,
                    'is_immutable' => false,
                    'deleted_at'   => null,
                    'created_at'   => $agora,
                    'updated_at'   => $agora,
                ],
            );

            $permissaoId = DB::table('permissions')
                ->where('name', $slug)->where('guard_name', 'web')
                ->value('id');

            $roles = DB::table('role_has_permissions as rp')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('p.name', $origem)
                ->where('p.guard_name', 'web')
                ->pluck('rp.role_id');

            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissaoId,
                    'role_id'       => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (array_keys(self::SLUGS) as $slug) {
            $permissaoId = DB::table('permissions')
                ->where('name', $slug)->where('guard_name', 'web')
                ->value('id');

            if ($permissaoId !== null) {
                DB::table('role_has_permissions')->where('permission_id', $permissaoId)->delete();
                DB::table('model_has_permissions')->where('permission_id', $permissaoId)->delete();
                DB::table('permissions')->where('id', $permissaoId)->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
