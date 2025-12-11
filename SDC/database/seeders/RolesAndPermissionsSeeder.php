<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ========================================================================
        // DEFINIR PERMISSIONS (Permissões Granulares por Módulo)
        // ========================================================================

        $permissions = [
            // USERS - Gestão de Usuários
            [
                'name' => 'Visualizar Usuários',
                'slug' => 'users.view',
                'description' => 'Pode visualizar lista de usuários',
                'group' => 'users',
            ],
            [
                'name' => 'Criar Usuários',
                'slug' => 'users.create',
                'description' => 'Pode criar novos usuários',
                'group' => 'users',
            ],
            [
                'name' => 'Editar Usuários',
                'slug' => 'users.edit',
                'description' => 'Pode editar usuários existentes',
                'group' => 'users',
            ],
            [
                'name' => 'Deletar Usuários',
                'slug' => 'users.delete',
                'description' => 'Pode deletar usuários',
                'group' => 'users',
            ],

            // ROLES - Gestão de Cargos/Papéis
            [
                'name' => 'Visualizar Cargos',
                'slug' => 'roles.view',
                'description' => 'Pode visualizar cargos do sistema',
                'group' => 'roles',
            ],
            [
                'name' => 'Criar Cargos',
                'slug' => 'roles.create',
                'description' => 'Pode criar novos cargos',
                'group' => 'roles',
            ],
            [
                'name' => 'Editar Cargos',
                'slug' => 'roles.edit',
                'description' => 'Pode editar cargos existentes',
                'group' => 'roles',
            ],
            [
                'name' => 'Deletar Cargos',
                'slug' => 'roles.delete',
                'description' => 'Pode deletar cargos',
                'group' => 'roles',
            ],

            // PERMISSIONS - Gestão de Permissões
            [
                'name' => 'Visualizar Permissões',
                'slug' => 'permissions.view',
                'description' => 'Pode visualizar permissões do sistema',
                'group' => 'permissions',
            ],
            [
                'name' => 'Gerenciar Permissões',
                'slug' => 'permissions.manage',
                'description' => 'Pode atribuir/remover permissões',
                'group' => 'permissions',
            ],

            // PAE - Plano de Auxílio Emergencial
            [
                'name' => 'Visualizar Empreendimentos',
                'slug' => 'pae.empreendimentos.view',
                'description' => 'Pode visualizar empreendimentos do PAE',
                'group' => 'pae',
            ],
            [
                'name' => 'Criar Empreendimentos',
                'slug' => 'pae.empreendimentos.create',
                'description' => 'Pode criar novos empreendimentos',
                'group' => 'pae',
            ],
            [
                'name' => 'Editar Empreendimentos',
                'slug' => 'pae.empreendimentos.edit',
                'description' => 'Pode editar empreendimentos existentes',
                'group' => 'pae',
            ],
            [
                'name' => 'Deletar Empreendimentos',
                'slug' => 'pae.empreendimentos.delete',
                'description' => 'Pode deletar empreendimentos',
                'group' => 'pae',
            ],
            [
                'name' => 'Aprovar Empreendimentos',
                'slug' => 'pae.empreendimentos.approve',
                'description' => 'Pode aprovar empreendimentos',
                'group' => 'pae',
            ],

            // RAT - Relatório de Atendimento Técnico
            [
                'name' => 'Visualizar Protocolos',
                'slug' => 'rat.protocolos.view',
                'description' => 'Pode visualizar protocolos do RAT',
                'group' => 'rat',
            ],
            [
                'name' => 'Criar Protocolos',
                'slug' => 'rat.protocolos.create',
                'description' => 'Pode criar novos protocolos',
                'group' => 'rat',
            ],
            [
                'name' => 'Editar Protocolos',
                'slug' => 'rat.protocolos.edit',
                'description' => 'Pode editar protocolos existentes',
                'group' => 'rat',
            ],
            [
                'name' => 'Deletar Protocolos',
                'slug' => 'rat.protocolos.delete',
                'description' => 'Pode deletar protocolos',
                'group' => 'rat',
            ],
            [
                'name' => 'Finalizar Protocolos',
                'slug' => 'rat.protocolos.finalize',
                'description' => 'Pode finalizar protocolos',
                'group' => 'rat',
            ],

            // BI - Business Intelligence
            [
                'name' => 'Visualizar Dashboards',
                'slug' => 'bi.dashboards.view',
                'description' => 'Pode visualizar dashboards e relatórios',
                'group' => 'bi',
            ],
            [
                'name' => 'Exportar Relatórios',
                'slug' => 'bi.reports.export',
                'description' => 'Pode exportar relatórios e dados',
                'group' => 'bi',
            ],
            [
                'name' => 'Criar Dashboards',
                'slug' => 'bi.dashboards.create',
                'description' => 'Pode criar novos dashboards',
                'group' => 'bi',
            ],

            // INTEGRATIONS - Integrações
            [
                'name' => 'Visualizar Integrações',
                'slug' => 'integrations.view',
                'description' => 'Pode visualizar integrações configuradas',
                'group' => 'integrations',
            ],
            [
                'name' => 'Criar Integrações',
                'slug' => 'integrations.create',
                'description' => 'Pode criar novas integrações',
                'group' => 'integrations',
            ],
            [
                'name' => 'Editar Integrações',
                'slug' => 'integrations.edit',
                'description' => 'Pode editar integrações existentes',
                'group' => 'integrations',
            ],
            [
                'name' => 'Executar Integrações',
                'slug' => 'integrations.execute',
                'description' => 'Pode executar integrações manualmente',
                'group' => 'integrations',
            ],

            // WEBHOOKS
            [
                'name' => 'Enviar Webhooks',
                'slug' => 'webhooks.send',
                'description' => 'Pode enviar webhooks',
                'group' => 'webhooks',
            ],
            [
                'name' => 'Visualizar Logs de Webhooks',
                'slug' => 'webhooks.logs.view',
                'description' => 'Pode visualizar logs de webhooks',
                'group' => 'webhooks',
            ],

            // SYSTEM - Administração do Sistema
            [
                'name' => 'Visualizar Logs do Sistema',
                'slug' => 'system.logs.view',
                'description' => 'Pode visualizar logs do sistema',
                'group' => 'system',
            ],
            [
                'name' => 'Limpar Cache',
                'slug' => 'system.cache.clear',
                'description' => 'Pode limpar cache do sistema',
                'group' => 'system',
            ],
            [
                'name' => 'Configurações do Sistema',
                'slug' => 'system.settings.manage',
                'description' => 'Pode gerenciar configurações do sistema',
                'group' => 'system',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // ========================================================================
        // DEFINIR ROLES (Cargos com Hierarquia de Acesso)
        // ========================================================================

        // 1. SUPER ADMIN - Acesso Total (Nível 0 - Máximo)
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrador',
                'description' => 'Acesso total ao sistema - Desenvolvimento e Manutenção',
            ]
        );

        // Super Admin tem TODAS as permissões
        $allPermissions = Permission::all()->pluck('id')->toArray();
        $superAdmin->syncPermissions($allPermissions);

        // 2. ADMIN - Administrador Geral (Nível 1)
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Administrador geral do sistema',
            ]
        );

        $adminPermissions = Permission::whereIn('slug', [
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles
            'roles.view',
            'roles.create',
            'roles.edit',

            // PAE
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'pae.empreendimentos.delete',
            'pae.empreendimentos.approve',

            // RAT
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'rat.protocolos.delete',
            'rat.protocolos.finalize',

            // BI
            'bi.dashboards.view',
            'bi.reports.export',
            'bi.dashboards.create',

            // Integrations
            'integrations.view',
            'integrations.create',
            'integrations.edit',
            'integrations.execute',

            // Webhooks
            'webhooks.send',
            'webhooks.logs.view',

            // System
            'system.logs.view',
            'system.cache.clear',
        ])->pluck('id')->toArray();

        $admin->syncPermissions($adminPermissions);

        // 3. GESTOR - Gestor de Área (Nível 2)
        $manager = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Gestor',
                'description' => 'Gestor de área - Pode aprovar e gerenciar módulos',
            ]
        );

        $managerPermissions = Permission::whereIn('slug', [
            // Users (apenas visualizar)
            'users.view',

            // PAE
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'pae.empreendimentos.approve',

            // RAT
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'rat.protocolos.finalize',

            // BI
            'bi.dashboards.view',
            'bi.reports.export',

            // Integrations
            'integrations.view',
            'integrations.execute',

            // Webhooks
            'webhooks.send',
            'webhooks.logs.view',
        ])->pluck('id')->toArray();

        $manager->syncPermissions($managerPermissions);

        // 4. ANALISTA - Analista Técnico (Nível 3)
        $analyst = Role::firstOrCreate(
            ['slug' => 'analyst'],
            [
                'name' => 'Analista',
                'description' => 'Analista técnico - Pode criar e editar registros',
            ]
        );

        $analystPermissions = Permission::whereIn('slug', [
            // PAE
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',

            // RAT
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',

            // BI
            'bi.dashboards.view',
            'bi.reports.export',

            // Integrations
            'integrations.view',

            // Webhooks
            'webhooks.logs.view',
        ])->pluck('id')->toArray();

        $analyst->syncPermissions($analystPermissions);

        // 5. OPERADOR - Operador de Sistema (Nível 4)
        $operator = Role::firstOrCreate(
            ['slug' => 'operator'],
            [
                'name' => 'Operador',
                'description' => 'Operador de sistema - Pode visualizar e criar registros básicos',
            ]
        );

        $operatorPermissions = Permission::whereIn('slug', [
            // PAE
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',

            // RAT
            'rat.protocolos.view',
            'rat.protocolos.create',

            // BI
            'bi.dashboards.view',
        ])->pluck('id')->toArray();

        $operator->syncPermissions($operatorPermissions);

        // 6. VISUALIZADOR - Somente Leitura (Nível 5)
        $viewer = Role::firstOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Visualizador',
                'description' => 'Acesso somente leitura - Pode apenas visualizar dados',
            ]
        );

        $viewerPermissions = Permission::whereIn('slug', [
            // PAE
            'pae.empreendimentos.view',

            // RAT
            'rat.protocolos.view',

            // BI
            'bi.dashboards.view',
        ])->pluck('id')->toArray();

        $viewer->syncPermissions($viewerPermissions);

        // 7. USER - Usuário Padrão (Nível 6 - Mínimo)
        $user = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'Usuário',
                'description' => 'Usuário padrão do sistema',
            ]
        );

        $userPermissions = Permission::whereIn('slug', [
            'pae.empreendimentos.view',
            'rat.protocolos.view',
        ])->pluck('id')->toArray();

        $user->syncPermissions($userPermissions);

        $this->command->info('✅ Roles e Permissions criadas com sucesso!');
        $this->command->info('');
        $this->command->info('📋 Hierarquia de Cargos:');
        $this->command->info('  Nível 0: Super Administrador (todas as permissões)');
        $this->command->info('  Nível 1: Administrador (gerenciamento completo)');
        $this->command->info('  Nível 2: Gestor (aprovação e gerenciamento)');
        $this->command->info('  Nível 3: Analista (criação e edição)');
        $this->command->info('  Nível 4: Operador (criação básica)');
        $this->command->info('  Nível 5: Visualizador (somente leitura)');
        $this->command->info('  Nível 6: Usuário (acesso mínimo)');
    }
}
