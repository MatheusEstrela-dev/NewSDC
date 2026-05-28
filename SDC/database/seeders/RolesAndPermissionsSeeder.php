<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seeder que utiliza config/permissions.php como fonte de verdade.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('permissions.guard', 'web');

        $this->seedPermissions($guard);
        $this->seedRoles($guard);
        $this->assignRolePermissions($guard);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->printSummary();
    }

    /**
     * Cria as permissoes a partir dos modulos definidos no config.
     */
    protected function seedPermissions(string $guard): void
    {
        $modules = config('permissions.modules', []);

        foreach ($modules as $moduleName => $groups) {
            foreach ($groups as $groupName => $actions) {
                foreach ($actions as $actionKey => $permissionSlug) {
                    Permission::updateOrCreate(
                        [
                            'name' => $permissionSlug,
                            'guard_name' => $guard,
                        ],
                        [
                            'name' => $permissionSlug,
                            'guard_name' => $guard,
                            'slug' => $permissionSlug,
                            'description' => $this->generatePermissionDescription($moduleName, $groupName, $actionKey),
                            'group' => strtolower($groupName),
                            'module' => strtolower($moduleName),
                            'is_active' => true,
                            'is_immutable' => in_array($permissionSlug, config('permissions.immutable_permissions', [])),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Cria os cargos a partir do config.
     */
    protected function seedRoles(string $guard): void
    {
        $levels = config('permissions.levels', []);
        $rolesMetadata = config('permissions.roles', []);

        foreach ($levels as $slug => $hierarchyLevel) {
            $metadata = $rolesMetadata[$slug] ?? [];
            $displayName = $metadata['name'] ?? ucfirst($slug);

            Role::updateOrCreate(
                [
                    'slug' => $slug,
                    'guard_name' => $guard,
                ],
                [
                    'name' => $displayName,
                    'guard_name' => $guard,
                    'slug' => $slug,
                    'hierarchy_level' => $hierarchyLevel,
                    'description' => $metadata['description'] ?? "Cargo {$slug}",
                    'is_active' => $metadata['is_active'] ?? true,
                ]
            );
        }
    }

    /**
     * Atribui permissoes aos cargos conforme definido no config.
     */
    protected function assignRolePermissions(string $guard): void
    {
        $rolePermissions = config('permissions.role_permissions', []);
        $allPermissionSlugs = $this->getAllPermissionSlugs();

        foreach ($rolePermissions as $roleSlug => $permissions) {
            $role = Role::where('slug', $roleSlug)
                ->where('guard_name', $guard)
                ->first();

            if (!$role) {
                continue;
            }

            $expandedPermissions = $this->expandWildcardPermissions($permissions, $allPermissionSlugs);
            $role->syncPermissions($expandedPermissions);
        }

        $this->assignFullAccessRoles($guard);
    }

    /**
     * Desenvolvedor (super-admin) recebe TODAS as permissoes automaticamente.
     */
    protected function assignFullAccessRoles(string $guard): void
    {
        $superAdmin = Role::where('slug', 'super-admin')
            ->where('guard_name', $guard)
            ->first();

        if ($superAdmin) {
            $allPermissions = Permission::where('guard_name', $guard)->pluck('name')->toArray();
            $superAdmin->syncPermissions($allPermissions);
        }
    }

    /**
     * Expande permissoes com wildcard (ex: 'users.*' -> ['users.view', 'users.create', ...]).
     */
    protected function expandWildcardPermissions(array $permissions, array $allPermissionSlugs): array
    {
        $expanded = [];

        foreach ($permissions as $permission) {
            if (str_ends_with($permission, '.*')) {
                $prefix = substr($permission, 0, -2);
                foreach ($allPermissionSlugs as $slug) {
                    if (str_starts_with($slug, $prefix . '.')) {
                        $expanded[] = $slug;
                    }
                }
            } else {
                if (in_array($permission, $allPermissionSlugs)) {
                    $expanded[] = $permission;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Retorna todos os slugs de permissoes definidos nos modulos.
     */
    protected function getAllPermissionSlugs(): array
    {
        $modules = config('permissions.modules', []);
        $slugs = [];

        foreach ($modules as $groups) {
            foreach ($groups as $actions) {
                foreach ($actions as $slug) {
                    $slugs[] = $slug;
                }
            }
        }

        return $slugs;
    }

    /**
     * Gera descricao automatica para permissao.
     */
    protected function generatePermissionDescription(string $module, string $group, string $action): string
    {
        $actionLabels = [
            'view' => 'Visualizar',
            'create' => 'Criar',
            'edit' => 'Editar',
            'delete' => 'Deletar',
            'approve' => 'Aprovar',
            'assign' => 'Atribuir',
            'atribuir' => 'Atribuir',
            'finalize' => 'Finalizar',
            'manage' => 'Gerenciar',
            'execute' => 'Executar',
            'export' => 'Exportar',
            'send' => 'Enviar',
            'logs' => 'Visualizar Logs',
            'cache' => 'Limpar Cache',
            'settings' => 'Configuracoes',
            'print' => 'Imprimir',
            'pdf' => 'Gerar PDF',
            'history' => 'Visualizar Historico',
            'arquivar' => 'Arquivar',
            'validar' => 'Validar',
            'attachments' => 'Gerenciar Anexos',
            'desvincular' => 'Desvincular',
        ];

        $actionLabel = $actionLabels[$action] ?? ucfirst($action);
        return "{$actionLabel} {$group} ({$module})";
    }

    /**
     * Imprime resumo da execucao.
     */
    protected function printSummary(): void
    {
        $this->command->info('Roles e Permissions sincronizadas via config/permissions.php');
        $this->command->info('');
        $this->command->info('Hierarquia de Cargos:');

        $levels = config('permissions.levels', []);
        $rolesMetadata = config('permissions.roles', []);

        foreach ($levels as $slug => $level) {
            $name = $rolesMetadata[$slug]['name'] ?? ucfirst($slug);
            $this->command->info("  Nivel {$level}: {$name} ({$slug})");
        }
    }
}
