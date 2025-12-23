<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Permission::class => \App\Policies\PermissionPolicy::class,
        \App\Models\Empreendimento::class => \App\Policies\EmpreendimentoPolicy::class,
        \App\Models\Protocolo::class => \App\Policies\ProtocoloPolicy::class,
        \App\Models\Entrada::class => \App\Policies\EntradaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super Admin tem todas as permissões (bypass)
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // ==================================================================
        // GATES BASEADOS EM ROLES (Hierarquia)
        // ==================================================================

        Gate::define('is-admin', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        Gate::define('is-manager', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager']);
        });

        Gate::define('is-analyst', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'analyst']);
        });

        Gate::define('is-operator', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'analyst', 'operator']);
        });
    }
}
