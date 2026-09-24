<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Slug `tdap.viagens.reprovar`: o COMPDEC atesta que a agua NAO chegou.
 *
 * Criado aqui, e nao so no RolesAndPermissionsSeeder, para o deploy nao
 * depender de reseed: sem o slug no banco o `can:` da rota nega a todos.
 * Vai para quem ja confirma -- confirmar e reprovar sao as duas respostas do
 * mesmo ato municipal, e quem hoje confirma nao pode perder metade dele.
 *
 * Query builder, e nao os models: os eventos deles limpam o cache a cada
 * save, e a migration so precisa limpar uma vez, no fim.
 */
return new class extends Migration
{
    private const SLUG = 'tdap.viagens.reprovar';

    private const SLUG_ORIGEM = 'tdap.viagens.confirmar';

    public function up(): void
    {
        $agora = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::SLUG, 'guard_name' => 'web'],
            [
                'slug'         => self::SLUG,
                'description'  => 'Tdap - Viagens - Reprovar',
                'group'        => 'viagens',
                'module'       => 'tdap',
                'is_active'    => true,
                'is_immutable' => false,
                'deleted_at'   => null,
                'created_at'   => $agora,
                'updated_at'   => $agora,
            ],
        );

        $permissaoId = DB::table('permissions')
            ->where('name', self::SLUG)->where('guard_name', 'web')
            ->value('id');

        $rolesQueConfirmam = DB::table('role_has_permissions as rp')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('p.name', self::SLUG_ORIGEM)
            ->where('p.guard_name', 'web')
            ->pluck('rp.role_id');

        foreach ($rolesQueConfirmam as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissaoId,
                'role_id'       => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissaoId = DB::table('permissions')
            ->where('name', self::SLUG)->where('guard_name', 'web')
            ->value('id');

        if ($permissaoId !== null) {
            DB::table('role_has_permissions')->where('permission_id', $permissaoId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissaoId)->delete();
            DB::table('permissions')->where('id', $permissaoId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
