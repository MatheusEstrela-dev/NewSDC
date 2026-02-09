<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hierarquia de Cargos (Em Pedra)
    |--------------------------------------------------------------------------
    | O valor define o peso. Menores valores tem autoridade sobre maiores.
    | Esta configuracao e IMUTAVEL e serve como fonte de verdade do sistema.
    */
    'levels' => [
        'super-admin' => 0,
        'admin'       => 1,
        'manager'     => 2,
        'analyst'     => 3,
        'operator'    => 4,
        'viewer'      => 5,
        'user'        => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadados dos Cargos
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'super-admin' => [
            'name'        => 'Super Admin',
            'description' => 'Acesso total e irrestrito ao sistema - Desenvolvimento e Manutencao',
            'is_active'   => true,
        ],
        'admin' => [
            'name'        => 'Administrador',
            'description' => 'Administrador geral do sistema',
            'is_active'   => true,
        ],
        'manager' => [
            'name'        => 'Gestor',
            'description' => 'Gestor de area - Pode aprovar e gerenciar modulos',
            'is_active'   => true,
        ],
        'analyst' => [
            'name'        => 'Analista',
            'description' => 'Analista tecnico - Pode criar e editar registros',
            'is_active'   => true,
        ],
        'operator' => [
            'name'        => 'Operador',
            'description' => 'Operador de sistema - Pode visualizar e criar registros basicos',
            'is_active'   => true,
        ],
        'viewer' => [
            'name'        => 'Visualizador',
            'description' => 'Acesso somente leitura - Pode apenas visualizar dados',
            'is_active'   => true,
        ],
        'user' => [
            'name'        => 'Usuario',
            'description' => 'Usuario padrao do sistema',
            'is_active'   => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Definicao de Modulos e Permissoes
    |--------------------------------------------------------------------------
    | Estrutura: Modulo -> Grupo -> Acoes (slugs)
    | Esta estrutura e sincronizada com o Frontend Vue via Inertia.
    */
    'modules' => [
        'SISTEMA' => [
            'Usuarios' => [
                'view'   => 'users.view',
                'create' => 'users.create',
                'edit'   => 'users.edit',
                'delete' => 'users.delete',
            ],
            'Cargos' => [
                'view'   => 'roles.view',
                'create' => 'roles.create',
                'edit'   => 'roles.edit',
                'delete' => 'roles.delete',
            ],
            'Permissoes' => [
                'view'   => 'permissions.view',
                'manage' => 'permissions.manage',
            ],
            'Configuracoes' => [
                'logs'     => 'system.logs.view',
                'cache'    => 'system.cache.clear',
                'settings' => 'system.settings.manage',
            ],
        ],
        'PAE' => [
            'Empreendimentos' => [
                'view'    => 'pae.empreendimentos.view',
                'create'  => 'pae.empreendimentos.create',
                'edit'    => 'pae.empreendimentos.edit',
                'delete'  => 'pae.empreendimentos.delete',
                'approve' => 'pae.empreendimentos.approve',
            ],
        ],
        'RAT' => [
            'Protocolos' => [
                'view'     => 'rat.protocolos.view',
                'create'   => 'rat.protocolos.create',
                'edit'     => 'rat.protocolos.edit',
                'delete'   => 'rat.protocolos.delete',
                'finalize' => 'rat.protocolos.finalize',
            ],
        ],
        'BI' => [
            'Dashboards' => [
                'view'   => 'bi.dashboards.view',
                'create' => 'bi.dashboards.create',
                'export' => 'bi.reports.export',
            ],
        ],
        'INTEGRACOES' => [
            'APIs' => [
                'view'    => 'integrations.view',
                'create'  => 'integrations.create',
                'edit'    => 'integrations.edit',
                'execute' => 'integrations.execute',
            ],
            'Webhooks' => [
                'send' => 'webhooks.send',
                'logs' => 'webhooks.logs.view',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissoes por Cargo
    |--------------------------------------------------------------------------
    | Define quais permissoes cada cargo possui.
    | Use '*' para todas as acoes de um modulo (ex: 'pae.*')
    | Super-admin automaticamente tem TODAS as permissoes.
    */
    'role_permissions' => [
        'admin' => [
            'users.*',
            'roles.*',
            'permissions.*',
            'pae.*',
            'rat.*',
            'bi.*',
            'integrations.*',
            'webhooks.*',
            'system.*',
        ],
        'manager' => [
            'users.view',
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'pae.empreendimentos.approve',
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'rat.protocolos.finalize',
            'bi.dashboards.view',
            'bi.reports.export',
            'integrations.view',
            'integrations.execute',
            'webhooks.send',
            'webhooks.logs.view',
        ],
        'analyst' => [
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'bi.dashboards.view',
            'bi.reports.export',
            'integrations.view',
            'webhooks.logs.view',
        ],
        'operator' => [
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'rat.protocolos.view',
            'rat.protocolos.create',
            'bi.dashboards.view',
        ],
        'viewer' => [
            'pae.empreendimentos.view',
            'rat.protocolos.view',
            'bi.dashboards.view',
        ],
        'user' => [
            'pae.empreendimentos.view',
            'rat.protocolos.view',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protecao de Permissoes Imutaveis
    |--------------------------------------------------------------------------
    | Permissoes que o sistema nao pode funcionar sem.
    | Estas permissoes NAO podem ser deletadas ou desativadas.
    */
    'immutable_permissions' => [
        'users.view',
        'users.edit',
        'roles.view',
        'permissions.view',
        'system.settings.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cargos Protegidos
    |--------------------------------------------------------------------------
    | Cargos que nao podem ser editados ou deletados.
    */
    'protected_roles' => [
        'super-admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nivel Padrao
    |--------------------------------------------------------------------------
    */
    'default_level' => 99,

    /*
    |--------------------------------------------------------------------------
    | Guard Padrao
    |--------------------------------------------------------------------------
    */
    'guard' => 'web',
];
