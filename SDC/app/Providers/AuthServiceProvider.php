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
        \App\Modules\Rat\Models\RatOcorrencia::class => \App\Policies\RatPolicy::class,
        \App\Models\Empreendimento::class => \App\Policies\EmpreendimentoPolicy::class,
        \App\Models\Protocolo::class => \App\Policies\ProtocoloPolicy::class,
        \App\Modules\Compdec\Models\Orgao::class => \App\Policies\OrgaoPolicy::class,
        \App\Modules\Compdec\Models\Prefeitura::class => \App\Policies\PrefeituraPolicy::class,
        \App\Modules\Compdec\Models\CompdecEquipe::class => \App\Policies\CompdecEquipePolicy::class,
        \App\Modules\Compdec\Models\CompdecAnexo::class => \App\Policies\CompdecAnexoPolicy::class,
        \App\Modules\Cisterna\Models\Cisterna::class => \App\Policies\CisternaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super Admin tem todas as permissoes (bypass via role apenas)
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
