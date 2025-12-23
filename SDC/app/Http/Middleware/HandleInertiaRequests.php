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
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'cpf' => $user->cpf ?? null,
                    'email' => $user->email ?? null,
                    // Spatie roles/permissions (usado para esconder/mostrar menus no frontend)
                    'is_super_admin' => method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false,
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : [],
                    'permissions' => method_exists($user, 'getAllPermissions')
                        ? $user->getAllPermissions()->pluck('name')->values()
                        : [],
                ] : null,
            ],
        ];
    }
}
