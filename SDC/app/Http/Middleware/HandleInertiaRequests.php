<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        return getenv('INERTIA_VERSION') ?: parent::version($request);
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
                'user' => fn() => $user ? $this->getCachedUserData($user) : null,
            ],
            'acl' => fn() => $this->getCachedAclConfig(),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info'    => fn() => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * Retorna dados do usuario autenticado com cache de 5 minutos.
     * Evita queries repetitivas de roles/permissions a cada navegacao SPA.
     */
    protected function getCachedUserData($user): array
    {
        $cacheKey = "inertia_user_data_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            // Eager load roles+permissions se nao carregados
            if (!$user->relationLoaded('roles')) {
                $user->load(['roles.permissions', 'permissions']);
            }
            return $this->getUserData($user);
        });
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
        if (!method_exists($user, 'permissions') || !method_exists($user, 'roles')) {
            return [];
        }

        // Usar relationships ja eager-loaded para evitar N+1
        $rolePermissions = $user->roles
            ->flatMap(fn($role) => $role->permissions->pluck('name'))
            ->toArray();

        $directPermissions = $user->permissions->pluck('name')->toArray();

        return array_values(array_unique(array_merge($rolePermissions, $directPermissions)));
    }

    /**
     * Retorna configuracao ACL com cache de 10 minutos.
     * Dados de config raramente mudam - nao precisam ser lidos do disco a cada request.
     */
    protected function getCachedAclConfig(): array
    {
        return Cache::remember('inertia_acl_config', 600, function () {
            return $this->getAclConfig();
        });
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
