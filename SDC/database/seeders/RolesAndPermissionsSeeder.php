<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // O projeto usa auth guard padrão "web" (sessão) para a UI.
        // Manter consistente com config/auth.php evita inconsistências no Spatie.
        $guard = config('auth.defaults.guard', 'web');

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
            Permission::updateOrCreate(
                [
                    'name' => $permission['slug'],
                    'guard_name' => $guard,
                ],
                [
                    'name' => $permission['slug'], // Spatie usa `name` como identificador
                    'guard_name' => $guard,
                    'slug' => $permission['slug'],
                    'description' => $permission['description'] ?? null,
                    'group' => $permission['group'] ?? 'general',
                    // module pode ser derivado do group (ou preenchido manualmente)
                    'module' => $permission['group'] ?? null,
                    'is_active' => true,
                    'is_immutable' => false,
                ]
            );
        }

        // ========================================================================
        // DEFINIR ROLES (Cargos com Hierarquia de Acesso)
        // ========================================================================

        // 1. SUPER ADMIN - Acesso Total (Nível 0 - Máximo)
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => $guard],
            [
                'name' => 'super-admin',
                'guard_name' => $guard,
                'slug' => 'super-admin',
                'hierarchy_level' => 0,
                'description' => 'Acesso total ao sistema - Desenvolvimento e Manutenção',
                'is_active' => true,
            ]
        );

        // Super Admin tem TODAS as permissões
        $allPermissionNames = Permission::where('guard_name', $guard)->pluck('name')->toArray();
        $superAdmin->syncPermissions($allPermissionNames);

        // 2. ADMIN - Administrador Geral (Nível 1)
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => $guard],
            [
                'name' => 'admin',
                'guard_name' => $guard,
                'slug' => 'admin',
                'hierarchy_level' => 1,
                'description' => 'Administrador geral do sistema',
                'is_active' => true,
            ]
        );

        $adminPermissions = [
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Permissions
            'permissions.view',
            'permissions.manage',

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
            'system.settings.manage',
        ];

        $admin->syncPermissions($adminPermissions);

        // 3. GESTOR - Gestor de Área (Nível 2)
        $manager = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => $guard],
            [
                'name' => 'manager',
                'guard_name' => $guard,
                'slug' => 'manager',
                'hierarchy_level' => 2,
                'description' => 'Gestor de área - Pode aprovar e gerenciar módulos',
                'is_active' => true,
            ]
        );

        $managerPermissions = [
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
        ];

        $manager->syncPermissions($managerPermissions);

        // 4. ANALISTA - Analista Técnico (Nível 3)
        $analyst = Role::firstOrCreate(
            ['name' => 'analyst', 'guard_name' => $guard],
            [
                'name' => 'analyst',
                'guard_name' => $guard,
                'slug' => 'analyst',
                'hierarchy_level' => 3,
                'description' => 'Analista técnico - Pode criar e editar registros',
                'is_active' => true,
            ]
        );

        $analystPermissions = [
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
        ];

        $analyst->syncPermissions($analystPermissions);

        // 5. OPERADOR - Operador de Sistema (Nível 4)
        $operator = Role::firstOrCreate(
            ['name' => 'operator', 'guard_name' => $guard],
            [
                'name' => 'operator',
                'guard_name' => $guard,
                'slug' => 'operator',
                'hierarchy_level' => 4,
                'description' => 'Operador de sistema - Pode visualizar e criar registros básicos',
                'is_active' => true,
            ]
        );

        $operatorPermissions = [
            // PAE
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',

            // RAT
            'rat.protocolos.view',
            'rat.protocolos.create',

            // BI
            'bi.dashboards.view',
        ];

        $operator->syncPermissions($operatorPermissions);

        // 6. VISUALIZADOR - Somente Leitura (Nível 5)
        $viewer = Role::firstOrCreate(
            ['name' => 'viewer', 'guard_name' => $guard],
            [
                'name' => 'viewer',
                'guard_name' => $guard,
                'slug' => 'viewer',
                'hierarchy_level' => 5,
                'description' => 'Acesso somente leitura - Pode apenas visualizar dados',
                'is_active' => true,
            ]
        );

        $viewerPermissions = [
            // PAE
            'pae.empreendimentos.view',

            // RAT
            'rat.protocolos.view',

            // BI
            'bi.dashboards.view',
        ];

        $viewer->syncPermissions($viewerPermissions);

        // 7. USER - Usuário Padrão (Nível 6 - Mínimo)
        $user = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => $guard],
            [
                'name' => 'user',
                'guard_name' => $guard,
                'slug' => 'user',
                'hierarchy_level' => 6,
                'description' => 'Usuário padrão do sistema',
                'is_active' => true,
            ]
        );

        $userPermissions = [
            'pae.empreendimentos.view',
            'rat.protocolos.view',
        ];

        $user->syncPermissions($userPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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
