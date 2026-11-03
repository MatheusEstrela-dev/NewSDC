<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissoes do modulo Notas Tecnicas. Migration principal das permissoes do
 * modulo: fases seguintes acrescentam as suas aqui. Espelha o bloco
 * NOTAS_TECNICAS de config/permissions.php.
 *
 * Criadas aqui, e nao so no seeder, para o deploy nao depender de reseed.
 *
 * Leitura vai para todo perfil interno; cadastrar, versionar, publicar,
 * revogar, organizar categorias e ver quem leu ficam com admin e manager.
 * Perfis municipais (compdec) e cidadao nao recebem nada.
 *
 * Query builder, e nao os models: os eventos deles limpam o cache a cada
 * save; a migration limpa uma vez, no fim.
 */
return new class extends Migration
{
    private const PERMISSOES = [
        'notas_tecnicas.notas.view'         => ['Notas Tecnicas - Notas - Ver', 'notas'],
        'notas_tecnicas.notas.create'       => ['Notas Tecnicas - Notas - Cadastrar', 'notas'],
        'notas_tecnicas.notas.edit'         => ['Notas Tecnicas - Notas - Editar e enviar versao', 'notas'],
        'notas_tecnicas.notas.publicar'     => ['Notas Tecnicas - Notas - Publicar ou revogar', 'notas'],
        'notas_tecnicas.notas.delete'       => ['Notas Tecnicas - Notas - Excluir', 'notas'],
        'notas_tecnicas.categorias.manage'  => ['Notas Tecnicas - Categorias - Organizar', 'categorias'],
        'notas_tecnicas.leituras.view'      => ['Notas Tecnicas - Leituras - Ver quem leu', 'leituras'],
    ];

    private const GESTAO = [
        'notas_tecnicas.notas.view',
        'notas_tecnicas.notas.create',
        'notas_tecnicas.notas.edit',
        'notas_tecnicas.notas.publicar',
        'notas_tecnicas.notas.delete',
        'notas_tecnicas.categorias.manage',
        'notas_tecnicas.leituras.view',
    ];

    /** Role => permissoes concedidas. */
    private const ROLES = [
        'admin'    => self::GESTAO,
        'manager'  => self::GESTAO,
        'analyst'  => ['notas_tecnicas.notas.view'],
        'operator' => ['notas_tecnicas.notas.view'],
        'viewer'   => ['notas_tecnicas.notas.view'],
        'user'     => ['notas_tecnicas.notas.view'],
    ];

    public function up(): void
    {
        $agora = now();

        foreach (self::PERMISSOES as $slug => [$descricao, $grupo]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $slug, 'guard_name' => 'web'],
                [
                    'slug'         => $slug,
                    'description'  => $descricao,
                    'group'        => $grupo,
                    'module'       => 'notas_tecnicas',
                    'is_active'    => true,
                    'is_immutable' => false,
                    'deleted_at'   => null,
                    'created_at'   => $agora,
                    'updated_at'   => $agora,
                ],
            );
        }

        $permissoes = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSOES))
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        // Role pelo slug: `name` guarda o rotulo exibido ("Administrador", "Gestor").
        foreach (self::ROLES as $role => $slugs) {
            $roleId = DB::table('roles')->where('slug', $role)->where('guard_name', 'web')->value('id');

            if ($roleId === null) {
                continue;
            }

            foreach ($slugs as $slug) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissoes[$slug],
                    'role_id'       => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSOES))
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
