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
        //
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
        // GATES PARA USERS (Gestão de Usuários)
        // ==================================================================

        Gate::define('users.view', function ($user) {
            return $user->hasPermission('users.view');
        });

        Gate::define('users.create', function ($user) {
            return $user->hasPermission('users.create');
        });

        Gate::define('users.edit', function ($user) {
            return $user->hasPermission('users.edit');
        });

        Gate::define('users.delete', function ($user) {
            return $user->hasPermission('users.delete');
        });

        // ==================================================================
        // GATES PARA ROLES (Gestão de Cargos)
        // ==================================================================

        Gate::define('roles.view', function ($user) {
            return $user->hasPermission('roles.view');
        });

        Gate::define('roles.create', function ($user) {
            return $user->hasPermission('roles.create');
        });

        Gate::define('roles.edit', function ($user) {
            return $user->hasPermission('roles.edit');
        });

        Gate::define('roles.delete', function ($user) {
            return $user->hasPermission('roles.delete');
        });

        // ==================================================================
        // GATES PARA PERMISSIONS (Gestão de Permissões)
        // ==================================================================

        Gate::define('permissions.view', function ($user) {
            return $user->hasPermission('permissions.view');
        });

        Gate::define('permissions.manage', function ($user) {
            return $user->hasPermission('permissions.manage');
        });

        // ==================================================================
        // GATES PARA PAE (Plano de Auxílio Emergencial)
        // ==================================================================

        Gate::define('pae.empreendimentos.view', function ($user) {
            return $user->hasPermission('pae.empreendimentos.view');
        });

        Gate::define('pae.empreendimentos.create', function ($user) {
            return $user->hasPermission('pae.empreendimentos.create');
        });

        Gate::define('pae.empreendimentos.edit', function ($user) {
            return $user->hasPermission('pae.empreendimentos.edit');
        });

        Gate::define('pae.empreendimentos.delete', function ($user) {
            return $user->hasPermission('pae.empreendimentos.delete');
        });

        Gate::define('pae.empreendimentos.approve', function ($user) {
            return $user->hasPermission('pae.empreendimentos.approve');
        });

        // ==================================================================
        // GATES PARA RAT (Relatório de Atendimento Técnico)
        // ==================================================================

        Gate::define('rat.protocolos.view', function ($user) {
            return $user->hasPermission('rat.protocolos.view');
        });

        Gate::define('rat.protocolos.create', function ($user) {
            return $user->hasPermission('rat.protocolos.create');
        });

        Gate::define('rat.protocolos.edit', function ($user) {
            return $user->hasPermission('rat.protocolos.edit');
        });

        Gate::define('rat.protocolos.delete', function ($user) {
            return $user->hasPermission('rat.protocolos.delete');
        });

        Gate::define('rat.protocolos.finalize', function ($user) {
            return $user->hasPermission('rat.protocolos.finalize');
        });

        // ==================================================================
        // GATES PARA BI (Business Intelligence)
        // ==================================================================

        Gate::define('bi.dashboards.view', function ($user) {
            return $user->hasPermission('bi.dashboards.view');
        });

        Gate::define('bi.reports.export', function ($user) {
            return $user->hasPermission('bi.reports.export');
        });

        Gate::define('bi.dashboards.create', function ($user) {
            return $user->hasPermission('bi.dashboards.create');
        });

        // ==================================================================
        // GATES PARA INTEGRATIONS (Integrações)
        // ==================================================================

        Gate::define('integrations.view', function ($user) {
            return $user->hasPermission('integrations.view');
        });

        Gate::define('integrations.create', function ($user) {
            return $user->hasPermission('integrations.create');
        });

        Gate::define('integrations.edit', function ($user) {
            return $user->hasPermission('integrations.edit');
        });

        Gate::define('integrations.execute', function ($user) {
            return $user->hasPermission('integrations.execute');
        });

        // ==================================================================
        // GATES PARA WEBHOOKS
        // ==================================================================

        Gate::define('webhooks.send', function ($user) {
            return $user->hasPermission('webhooks.send');
        });

        Gate::define('webhooks.logs.view', function ($user) {
            return $user->hasPermission('webhooks.logs.view');
        });

        // ==================================================================
        // GATES PARA SYSTEM (Administração do Sistema)
        // ==================================================================

        Gate::define('system.logs.view', function ($user) {
            return $user->hasPermission('system.logs.view');
        });

        Gate::define('system.cache.clear', function ($user) {
            return $user->hasPermission('system.cache.clear');
        });

        Gate::define('system.settings.manage', function ($user) {
            return $user->hasPermission('system.settings.manage');
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
