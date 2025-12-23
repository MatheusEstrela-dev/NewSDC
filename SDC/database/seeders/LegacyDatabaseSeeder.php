<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class LegacyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa cache do Spatie antes de sincronizar
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $legacy = DB::connection('legacy');
            // força a conexão para falhar cedo se não estiver configurada
            $legacy->getPdo();
        } catch (\Throwable $e) {
            $this->command?->warn('⚠️ Seed legado ignorado: conexão DB `legacy` não está disponível/configurada.');
            return;
        }

        $rolesTable = config('permission.table_names.roles', 'roles');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelHasPermissionsTable = config('permission.table_names.model_has_permissions', 'model_has_permissions');

        $legacyRolesCols = $legacy->getSchemaBuilder()->getColumnListing($rolesTable);
        $legacyPermCols = $legacy->getSchemaBuilder()->getColumnListing($permissionsTable);

        $roleIdMap = []; // legacy_role_id => local_role_id
        $permIdMap = []; // legacy_permission_id => local_permission_id

        // ---------------------------------------------------------------------
        // 1) Roles (Cargos)
        // ---------------------------------------------------------------------
        foreach ($legacy->table($rolesTable)->get() as $r) {
            $data = [
                'name' => $r->name,
                'guard_name' => $r->guard_name ?? 'sanctum',
            ];

            if (in_array('slug', $legacyRolesCols, true)) {
                $data['slug'] = $r->slug ?: $r->name;
            }
            if (in_array('hierarchy_level', $legacyRolesCols, true)) {
                $data['hierarchy_level'] = $r->hierarchy_level ?? 99;
            }
            if (in_array('description', $legacyRolesCols, true)) {
                $data['description'] = $r->description ?? null;
            }
            if (in_array('is_active', $legacyRolesCols, true)) {
                $data['is_active'] = (bool) ($r->is_active ?? true);
            }

            $role = Role::updateOrCreate(
                ['name' => $data['name'], 'guard_name' => $data['guard_name']],
                $data
            );

            $roleIdMap[$r->id] = $role->id;
        }

        // ---------------------------------------------------------------------
        // 2) Permissions
        // ---------------------------------------------------------------------
        foreach ($legacy->table($permissionsTable)->get() as $p) {
            $data = [
                'name' => $p->name,
                'guard_name' => $p->guard_name ?? 'sanctum',
            ];

            if (in_array('slug', $legacyPermCols, true)) {
                $data['slug'] = $p->slug ?: $p->name;
            }
            if (in_array('description', $legacyPermCols, true)) {
                $data['description'] = $p->description ?? null;
            }
            if (in_array('group', $legacyPermCols, true)) {
                $data['group'] = $p->group ?? 'general';
            }
            if (in_array('module', $legacyPermCols, true)) {
                $data['module'] = $p->module ?? null;
            }
            if (in_array('is_active', $legacyPermCols, true)) {
                $data['is_active'] = (bool) ($p->is_active ?? true);
            }
            if (in_array('is_immutable', $legacyPermCols, true)) {
                $data['is_immutable'] = (bool) ($p->is_immutable ?? false);
            }

            $perm = Permission::updateOrCreate(
                ['name' => $data['name'], 'guard_name' => $data['guard_name']],
                $data
            );

            $permIdMap[$p->id] = $perm->id;
        }

        // ---------------------------------------------------------------------
        // 3) Role -> Permissions (Cargos)
        // ---------------------------------------------------------------------
        $pivotCols = $legacy->getSchemaBuilder()->getColumnListing($roleHasPermissionsTable);
        $pivotRoleKey = in_array('role_id', $pivotCols, true) ? 'role_id' : 'role_id';
        $pivotPermKey = in_array('permission_id', $pivotCols, true) ? 'permission_id' : 'permission_id';

        $roleHasPermissionsRows = $legacy->table($roleHasPermissionsTable)->get();

        foreach ($roleHasPermissionsRows as $row) {
            $legacyRoleId = $row->{$pivotRoleKey};
            $legacyPermId = $row->{$pivotPermKey};

            $localRoleId = $roleIdMap[$legacyRoleId] ?? null;
            $localPermId = $permIdMap[$legacyPermId] ?? null;

            if (!$localRoleId || !$localPermId) {
                continue;
            }

            DB::table($roleHasPermissionsTable)->updateOrInsert(
                ['role_id' => $localRoleId, 'permission_id' => $localPermId],
                []
            );
        }

        // ---------------------------------------------------------------------
        // 4) Model -> Roles (Geral - Polimórfico)
        // ---------------------------------------------------------------------
        $mhrCols = $legacy->getSchemaBuilder()->getColumnListing($modelHasRolesTable);
        $mhrRoleKey = in_array('role_id', $mhrCols, true) ? 'role_id' : 'role_id';
        $mhrModelTypeKey = in_array('model_type', $mhrCols, true) ? 'model_type' : 'model_type';
        $mhrModelIdKey = in_array('model_id', $mhrCols, true) ? 'model_id' : 'model_id';

        foreach ($legacy->table($modelHasRolesTable)->get() as $row) {
            $legacyRoleId = $row->{$mhrRoleKey};
            $localRoleId = $roleIdMap[$legacyRoleId] ?? null;
            if (!$localRoleId) {
                continue;
            }

            $modelType = $row->{$mhrModelTypeKey};
            $modelId = (int) $row->{$mhrModelIdKey};

            // Importamos apenas User (ou qualquer model_type que termine em \User)
            if ($modelType !== User::class && !str_ends_with($modelType, '\\User')) {
                continue;
            }

            $user = User::find($modelId);
            if (!$user) {
                continue;
            }

            // Usando API do Spatie garante consistência/flush de cache
            $user->assignRole(Role::find($localRoleId));
        }

        // ---------------------------------------------------------------------
        // 5) Model -> Permissions (CRUD - Polimórfico)
        // ---------------------------------------------------------------------
        $mhpCols = $legacy->getSchemaBuilder()->getColumnListing($modelHasPermissionsTable);
        $mhpPermKey = in_array('permission_id', $mhpCols, true) ? 'permission_id' : 'permission_id';
        $mhpModelTypeKey = in_array('model_type', $mhpCols, true) ? 'model_type' : 'model_type';
        $mhpModelIdKey = in_array('model_id', $mhpCols, true) ? 'model_id' : 'model_id';

        foreach ($legacy->table($modelHasPermissionsTable)->get() as $row) {
            $legacyPermId = $row->{$mhpPermKey};
            $localPermId = $permIdMap[$legacyPermId] ?? null;
            if (!$localPermId) {
                continue;
            }

            $modelType = $row->{$mhpModelTypeKey};
            $modelId = (int) $row->{$mhpModelIdKey};

            if ($modelType !== User::class && !str_ends_with($modelType, '\\User')) {
                continue;
            }

            $user = User::find($modelId);
            if (!$user) {
                continue;
            }

            $user->givePermissionTo(Permission::find($localPermId));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('✅ Importação do banco legado concluída (roles/permissions + vínculos).');
    }
}


