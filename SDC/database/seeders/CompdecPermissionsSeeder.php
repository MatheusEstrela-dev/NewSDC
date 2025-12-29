<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CompdecPermissionsSeeder extends Seeder
{
    /**
     * Seed de Permissões do Módulo COMPDEC
     *
     * Estrutura:
     * - Usuários Comuns: Podem apenas visualizar órgãos
     * - Coordenadores COMPDEC: Podem gerenciar seus órgãos
     * - Administradores: Acesso total
     */
    public function run(): void
    {
        $guard = 'web';

        // ========================================
        // PERMISSÕES BÁSICAS
        // ========================================

        Permission::firstOrCreate(
            ['name' => 'compdec.view', 'guard_name' => $guard],
            ['description' => 'Visualizar lista de órgãos COMPDEC/REDEC/CEDEC']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.view-details', 'guard_name' => $guard],
            ['description' => 'Ver detalhes de um órgão específico']
        );

        // ========================================
        // PERMISSÕES DE GESTÃO
        // ========================================

        Permission::firstOrCreate(
            ['name' => 'compdec.manage', 'guard_name' => $guard],
            ['description' => 'Gerenciar órgãos (criar, editar, ativar/desativar)']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.create', 'guard_name' => $guard],
            ['description' => 'Criar novos órgãos']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.edit', 'guard_name' => $guard],
            ['description' => 'Editar informações de órgãos']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.delete', 'guard_name' => $guard],
            ['description' => 'Deletar órgãos']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.vincular-usuarios', 'guard_name' => $guard],
            ['description' => 'Vincular/desvincular usuários aos órgãos']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.gerenciar-hierarquia', 'guard_name' => $guard],
            ['description' => 'Gerenciar hierarquia (COMPDEC → REDEC → CEDEC)']
        );

        // ========================================
        // PERMISSÕES AVANÇADAS
        // ========================================

        Permission::firstOrCreate(
            ['name' => 'compdec.view-statistics', 'guard_name' => $guard],
            ['description' => 'Visualizar estatísticas e relatórios']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.export', 'guard_name' => $guard],
            ['description' => 'Exportar dados de órgãos']
        );

        Permission::firstOrCreate(
            ['name' => 'compdec.import', 'guard_name' => $guard],
            ['description' => 'Importar órgãos em lote (CSV, etc)']
        );

        // ========================================
        // ATRIBUIR PERMISSÕES AOS ROLES
        // ========================================

        // Super Admin: acesso total
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
                'compdec.manage',
                'compdec.create',
                'compdec.edit',
                'compdec.delete',
                'compdec.vincular-usuarios',
                'compdec.gerenciar-hierarquia',
                'compdec.view-statistics',
                'compdec.export',
                'compdec.import',
            ]);
        }

        // Admin: gerenciamento completo
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
                'compdec.manage',
                'compdec.create',
                'compdec.edit',
                'compdec.delete',
                'compdec.vincular-usuarios',
                'compdec.gerenciar-hierarquia',
                'compdec.view-statistics',
                'compdec.export',
                'compdec.import',
            ]);
        }

        // Manager: pode gerenciar órgãos e usuários
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
                'compdec.manage',
                'compdec.create',
                'compdec.edit',
                'compdec.vincular-usuarios',
                'compdec.view-statistics',
                'compdec.export',
            ]);
        }

        // Coordinator: pode gerenciar seu próprio órgão
        $coordinator = Role::where('slug', 'coordinator')->first();
        if ($coordinator) {
            $coordinator->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
                'compdec.edit',
                'compdec.view-statistics',
            ]);
        }

        // Analyst: visualização completa
        $analyst = Role::where('slug', 'analyst')->first();
        if ($analyst) {
            $analyst->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
                'compdec.view-statistics',
            ]);
        }

        // Operator: visualização básica
        $operator = Role::where('slug', 'operator')->first();
        if ($operator) {
            $operator->givePermissionTo([
                'compdec.view',
                'compdec.view-details',
            ]);
        }

        // User (usuário comum): apenas visualizar
        $user = Role::where('slug', 'user')->first();
        if ($user) {
            $user->givePermissionTo([
                'compdec.view',
            ]);
        }

        $this->command->info('✓ Permissões do módulo COMPDEC criadas com sucesso!');
    }
}
