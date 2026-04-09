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
        'admin' => 1,
        'manager' => 2,
        'analyst' => 3,
        'operator' => 4,
        'viewer' => 5,
        'user' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadados dos Cargos
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'super-admin' => [
            'name' => 'Desenvolvedor',
            'description' => 'Acesso total e irrestrito ao sistema - Desenvolvimento e Manutencao',
            'is_active' => true,
        ],
        'admin' => [
            'name' => 'Administrador',
            'description' => 'Administrador geral do sistema',
            'is_active' => true,
        ],
        'manager' => [
            'name' => 'Gestor',
            'description' => 'Gestor de area - Pode aprovar e gerenciar modulos',
            'is_active' => true,
        ],
        'analyst' => [
            'name' => 'Analista',
            'description' => 'Analista tecnico - Pode criar e editar registros',
            'is_active' => true,
        ],
        'operator' => [
            'name' => 'Operador',
            'description' => 'Operador de sistema - Pode visualizar e criar registros basicos',
            'is_active' => true,
        ],
        'viewer' => [
            'name' => 'Visualizador',
            'description' => 'Acesso somente leitura - Pode apenas visualizar dados',
            'is_active' => true,
        ],
        'user' => [
            'name' => 'Usuario',
            'description' => 'Usuario padrao do sistema',
            'is_active' => true,
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
                'view' => 'users.view',
                'create' => 'users.create',
                'edit' => 'users.edit',
                'delete' => 'users.delete',
            ],
            'Cargos' => [
                'view' => 'roles.view',
                'create' => 'roles.create',
                'edit' => 'roles.edit',
                'delete' => 'roles.delete',
            ],
            'Permissoes' => [
                'view' => 'permissions.view',
                'manage' => 'permissions.manage',
            ],
            'Configuracoes' => [
                'logs' => 'system.logs.view',
                'cache' => 'system.cache.clear',
                'settings' => 'system.settings.manage',
            ],
        ],
        'PAE' => [
            'Empreendimentos' => [
                'view' => 'pae.empreendimentos.view',
                'create' => 'pae.empreendimentos.create',
                'edit' => 'pae.empreendimentos.edit',
                'delete' => 'pae.empreendimentos.delete',
                'approve' => 'pae.empreendimentos.approve',
                'export' => 'pae.empreendimentos.export',
            ],
            'Protocolos' => [
                'view' => 'pae.protocolos.view',
                'create' => 'pae.protocolos.create',
                'edit' => 'pae.protocolos.edit',
                'delete' => 'pae.protocolos.delete',
                'atribuir' => 'pae.protocolos.atribuir',
                'export' => 'pae.protocolos.export',
            ],
        ],
        'RAT' => [
            'Protocolos' => [
                'view' => 'rat.protocolos.view',
                'create' => 'rat.protocolos.create',
                'edit' => 'rat.protocolos.edit',
                'delete' => 'rat.protocolos.delete',
                'finalize' => 'rat.protocolos.finalize',
                'export' => 'rat.protocolos.export',
            ],
        ],
        'DEMANDAS' => [
            'Chamados' => [
                'view' => 'demandas.chamados.view',
                'create' => 'demandas.chamados.create',
                'edit' => 'demandas.chamados.edit',
                'delete' => 'demandas.chamados.delete',
                'export' => 'demandas.chamados.export',
                'manage' => 'demandas.chamados.manage',
            ],
        ],
        'DECRETACOES' => [
            'Processos' => [
                'view' => 'decretacoes.processos.view',
                'create' => 'decretacoes.processos.create',
                'edit' => 'decretacoes.processos.edit',
                'delete' => 'decretacoes.processos.delete',
                'export' => 'decretacoes.processos.export',
            ],
        ],
        'AJUDA_HUMANITARIA' => [
            'Beneficiarios' => [
                'view' => 'humanitaria.beneficiarios.view',
                'create' => 'humanitaria.beneficiarios.create',
                'edit' => 'humanitaria.beneficiarios.edit',
                'delete' => 'humanitaria.beneficiarios.delete',
                'export' => 'humanitaria.beneficiarios.export',
            ],
        ],
        'TDAP' => [
            'Produtos' => [
                'view' => 'tdap.products.view',
                'create' => 'tdap.products.create',
                'edit' => 'tdap.products.edit',
                'delete' => 'tdap.products.delete',
            ],
            'Recebimentos' => [
                'view' => 'tdap.recebimentos.view',
                'create' => 'tdap.recebimentos.create',
                'processar' => 'tdap.recebimentos.processar',
            ],
            'Movimentacoes' => [
                'view' => 'tdap.movimentacoes.view',
                'create' => 'tdap.movimentacoes.create',
            ],
            'Admin' => [
                'admin' => 'tdap.admin',
            ],
        ],
        'TREINAMENTO' => [
            'Cursos' => [
                'view' => 'treinamento.cursos.view',
                'create' => 'treinamento.cursos.create',
                'edit' => 'treinamento.cursos.edit',
                'delete' => 'treinamento.cursos.delete',
                'export' => 'treinamento.cursos.export',
            ],
        ],
        'PLANTAO' => [
            'Turnos' => [
                'view' => 'plantao.turnos.view',
                'create' => 'plantao.turnos.create',
                'edit' => 'plantao.turnos.edit',
                'delete' => 'plantao.turnos.delete',
                'export' => 'plantao.turnos.export',
            ],
        ],
        'BI' => [
            'Dashboards' => [
                'view' => 'bi.dashboards.view',
                'create' => 'bi.dashboards.create',
                'export' => 'bi.reports.export',
            ],
        ],
        'INTEGRACOES' => [
            'APIs' => [
                'view' => 'integrations.view',
                'create' => 'integrations.create',
                'edit' => 'integrations.edit',
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
            'demandas.*',
            'decretacoes.*',
            'humanitaria.*',
            'tdap.*',
            'treinamento.*',
            'plantao.*',
            'bi.*',
            'integrations.*',
            'webhooks.*',
            'system.*',
        ],
        'manager' => [
            // PAE - CRUD completo exceto delete
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'pae.empreendimentos.approve',
            'pae.empreendimentos.export',
            'pae.protocolos.view',
            'pae.protocolos.create',
            'pae.protocolos.edit',
            'pae.protocolos.atribuir',
            'pae.protocolos.export',
            // RAT - CRUD completo exceto delete
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'rat.protocolos.finalize',
            'rat.protocolos.export',
            // Demandas - gestao completa
            'demandas.chamados.view',
            'demandas.chamados.create',
            'demandas.chamados.edit',
            'demandas.chamados.export',
            'demandas.chamados.manage',
            // Decretacoes - CRUD completo exceto delete
            'decretacoes.processos.view',
            'decretacoes.processos.create',
            'decretacoes.processos.edit',
            'decretacoes.processos.export',
            // Ajuda Humanitaria - CRUD completo exceto delete
            'humanitaria.beneficiarios.view',
            'humanitaria.beneficiarios.create',
            'humanitaria.beneficiarios.edit',
            'humanitaria.beneficiarios.export',
            // TDAP - gestao completa
            'tdap.products.view',
            'tdap.products.create',
            'tdap.products.edit',
            'tdap.recebimentos.view',
            'tdap.recebimentos.create',
            'tdap.recebimentos.processar',
            'tdap.movimentacoes.view',
            'tdap.movimentacoes.create',
            // Treinamento - gestao completa
            'treinamento.cursos.view',
            'treinamento.cursos.create',
            'treinamento.cursos.edit',
            'treinamento.cursos.export',
            // Plantao - gestao completa
            'plantao.turnos.view',
            'plantao.turnos.create',
            'plantao.turnos.edit',
            'plantao.turnos.export',
            // BI
            'bi.dashboards.view',
            'bi.reports.export',
            // Integracoes
            'integrations.view',
            'integrations.execute',
            'webhooks.send',
            'webhooks.logs.view',
        ],
        'analyst' => [
            // PAE - view, create, edit
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.empreendimentos.edit',
            'pae.protocolos.view',
            'pae.protocolos.create',
            'pae.protocolos.edit',
            'pae.protocolos.atribuir',
            // RAT - view, create, edit
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            // Demandas - view, create, edit
            'demandas.chamados.view',
            'demandas.chamados.create',
            'demandas.chamados.edit',
            // Decretacoes - view, create, edit
            'decretacoes.processos.view',
            'decretacoes.processos.create',
            'decretacoes.processos.edit',
            // Ajuda Humanitaria - view, create, edit
            'humanitaria.beneficiarios.view',
            'humanitaria.beneficiarios.create',
            'humanitaria.beneficiarios.edit',
            // TDAP - view, create
            'tdap.products.view',
            'tdap.products.create',
            'tdap.recebimentos.view',
            'tdap.recebimentos.create',
            'tdap.movimentacoes.view',
            'tdap.movimentacoes.create',
            // Treinamento - view
            'treinamento.cursos.view',
            // Plantao - view, create, edit
            'plantao.turnos.view',
            'plantao.turnos.create',
            'plantao.turnos.edit',
            // BI
            'bi.dashboards.view',
            'bi.reports.export',
            // Integracoes
            'integrations.view',
            'webhooks.logs.view',
        ],
        'operator' => [
            // PAE - view, create
            'pae.empreendimentos.view',
            'pae.empreendimentos.create',
            'pae.protocolos.view',
            'pae.protocolos.create',
            // RAT - view, create
            'rat.protocolos.view',
            'rat.protocolos.create',
            // Demandas - view, create
            'demandas.chamados.view',
            'demandas.chamados.create',
            // Decretacoes - view
            'decretacoes.processos.view',
            // Ajuda Humanitaria - view, create
            'humanitaria.beneficiarios.view',
            'humanitaria.beneficiarios.create',
            // TDAP - view
            'tdap.products.view',
            'tdap.recebimentos.view',
            'tdap.movimentacoes.view',
            // Treinamento - view
            'treinamento.cursos.view',
            // Plantao - view, create
            'plantao.turnos.view',
            'plantao.turnos.create',
            // BI - view
            'bi.dashboards.view',
        ],
        'viewer' => [
            // Somente visualizacao em todos os modulos
            'pae.empreendimentos.view',
            'pae.protocolos.view',
            'rat.protocolos.view',
            'demandas.chamados.view',
            'decretacoes.processos.view',
            'humanitaria.beneficiarios.view',
            'tdap.products.view',
            'tdap.recebimentos.view',
            'tdap.movimentacoes.view',
            'treinamento.cursos.view',
            'plantao.turnos.view',
            'bi.dashboards.view',
        ],
        'user' => [
            // Acesso basico apenas
            'pae.empreendimentos.view',
            'pae.protocolos.view',
            'rat.protocolos.view',
            'demandas.chamados.view',
            'demandas.chamados.create',
            'treinamento.cursos.view',
            'plantao.turnos.view',
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
