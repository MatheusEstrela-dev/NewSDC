<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemandasPermissionsSeeder extends Seeder
{
    /**
     * Seed de Permissões do Módulo Demandas
     *
     * Estrutura:
     * - Usuários Comuns: Podem apenas abrir e ver suas próprias demandas
     * - Agentes TI: Podem gerenciar todas as demandas
     * - Super Admin: Acesso total
     */
    public function run(): void
    {
        $guard = 'web';

        // ========================================
        // PERMISSÕES PARA USUÁRIOS COMUNS
        // ========================================

        Permission::firstOrCreate(
            ['name' => 'demandas.view-own', 'guard_name' => $guard],
            ['description' => 'Ver minhas próprias demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.create', 'guard_name' => $guard],
            ['description' => 'Abrir novas demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.comment-own', 'guard_name' => $guard],
            ['description' => 'Comentar em minhas demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.attach-own', 'guard_name' => $guard],
            ['description' => 'Anexar arquivos em minhas demandas']
        );

        // ========================================
        // PERMISSÕES PARA AGENTES TI
        // ========================================

        Permission::firstOrCreate(
            ['name' => 'demandas.manage', 'guard_name' => $guard],
            ['description' => 'Gerenciar demandas (visualizar todas, atribuir, alterar status)']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.view-all', 'guard_name' => $guard],
            ['description' => 'Ver todas as demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.edit', 'guard_name' => $guard],
            ['description' => 'Editar demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.delete', 'guard_name' => $guard],
            ['description' => 'Deletar demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.assign', 'guard_name' => $guard],
            ['description' => 'Atribuir demandas a agentes']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.change-status', 'guard_name' => $guard],
            ['description' => 'Alterar status de demandas']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.approve', 'guard_name' => $guard],
            ['description' => 'Aprovar mudanças (CAB)']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.sla.manage', 'guard_name' => $guard],
            ['description' => 'Gerenciar definições de SLA']
        );

        Permission::firstOrCreate(
            ['name' => 'demandas.reports.view', 'guard_name' => $guard],
            ['description' => 'Visualizar relatórios e estatísticas de demandas']
        );

        // ========================================
        // ATRIBUIR PERMISSÕES AOS ROLES
        // ========================================

        // Super Admin: tudo
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.manage',
                'demandas.view-all',
                'demandas.edit',
                'demandas.delete',
                'demandas.assign',
                'demandas.change-status',
                'demandas.approve',
                'demandas.sla.manage',
                'demandas.reports.view',
            ]);
        }

        // Admin: gerenciamento completo
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.manage',
                'demandas.view-all',
                'demandas.edit',
                'demandas.delete',
                'demandas.assign',
                'demandas.change-status',
                'demandas.approve',
                'demandas.sla.manage',
                'demandas.reports.view',
            ]);
        }

        // Manager: pode gerenciar e atribuir
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.manage',
                'demandas.view-all',
                'demandas.assign',
                'demandas.change-status',
                'demandas.approve',
                'demandas.reports.view',
            ]);
        }

        // Coordinator: pode ver todas e alterar status
        $coordinator = Role::where('slug', 'coordinator')->first();
        if ($coordinator) {
            $coordinator->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.manage',
                'demandas.view-all',
                'demandas.change-status',
            ]);
        }

        // Analyst: pode trabalhar em demandas atribuídas
        $analyst = Role::where('slug', 'analyst')->first();
        if ($analyst) {
            $analyst->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.manage',
                'demandas.view-all',
                'demandas.change-status',
            ]);
        }

        // Operator: pode ver e comentar
        $operator = Role::where('slug', 'operator')->first();
        if ($operator) {
            $operator->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
                'demandas.view-all',
            ]);
        }

        // User (usuário comum): apenas suas próprias demandas
        $user = Role::where('slug', 'user')->first();
        if ($user) {
            $user->givePermissionTo([
                'demandas.view-own',
                'demandas.create',
                'demandas.comment-own',
                'demandas.attach-own',
            ]);
        }

        $this->command->info('✓ Permissões do módulo Demandas criadas com sucesso!');
    }
}
