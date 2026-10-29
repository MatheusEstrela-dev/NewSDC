<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Slugs do Acompanhamento de cronograma.
 *
 * - `tdap.acompanhamento.view` vai para quem ja ve cronogramas: o painel e
 *   outra leitura dos mesmos registros, com o mesmo recorte por municipio.
 * - `tdap.cronogramas.analisar` vai para quem ja valida viagem: e a CEDEC
 *   analisando, o mesmo responsavel da validacao.
 *
 * Mesmo molde de 2026_09_24_120100 (query builder, cache limpo uma vez no fim).
 */
return new class extends Migration
{
    /** slug novo => [descricao, grupo, slug de origem das atribuicoes] */
    private const SLUGS = [
        'tdap.acompanhamento.view'  => ['Tdap - Acompanhamento - View', 'acompanhamento', 'tdap.cronogramas.view'],
        'tdap.cronogramas.analisar' => ['Tdap - Cronogramas - Analisar', 'cronogramas', 'tdap.viagens.validar'],
    ];

    public function up(): void
    {
        $agora = now();

        foreach (self::SLUGS as $slug => [$descricao, $grupo, $origem]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $slug, 'guard_name' => 'web'],
                [
                    'slug'         => $slug,
                    'description'  => $descricao,
                    'group'        => $grupo,
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
