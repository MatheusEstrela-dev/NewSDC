<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? $this->getUserData($user) : null,
            ],
            'acl' => $this->getAclConfig(),
        ];
    }

    /**
     * Retorna dados do usuario autenticado.
     */
    protected function getUserData($user): array
    {
        $roles = [];
        if (method_exists($user, 'roles')) {
            $roles = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'hierarchy_level' => $role->hierarchy_level,
            ])->values()->toArray();
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'cpf' => $user->cpf ?? null,
            'email' => $user->email ?? null,
            'is_super_admin' => method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false,
            'roles' => $roles,
            'role_names' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : [],
            'permissions' => $this->getEffectivePermissions($user),
            'hierarchy_level' => method_exists($user, 'getHierarchyLevel')
                ? $user->getHierarchyLevel()
                : 99,
        ];
    }

    /**
     * Retorna as permissoes efetivas do usuario.
     * LOGICA ADITIVA: Permissoes do CARGO + Permissoes DIRETAS = Efetivas
     *
     * Tabelas envolvidas:
     * - role_has_permissions: permissoes do cargo
     * - model_has_permissions: permissoes diretas do usuario
     * - model_has_roles: cargos atribuidos ao usuario
     */
    protected function getEffectivePermissions($user): array
    {
        if (!method_exists($user, 'permissions') || !method_exists($user, 'getPermissionsViaRoles')) {
            return [];
        }

        $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->values()->toArray();
        $directPermissions = $user->permissions->pluck('name')->values()->toArray();

        return array_values(array_unique(array_merge($rolePermissions, $directPermissions)));
    }

    /**
     * Retorna configuracao ACL do config/permissions.php para o frontend.
     */
    protected function getAclConfig(): array
    {
        return [
            'levels' => config('permissions.levels', []),
            'modules' => config('permissions.modules', []),
            'protected_roles' => config('permissions.protected_roles', []),
            'immutable_permissions' => config('permissions.immutable_permissions', []),
            'default_level' => config('permissions.default_level', 99),
        ];
    }
}
