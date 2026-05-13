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
            // TDAP - Transporte e Distribuicao de Agua Potavel
            'Dashboard' => [
                'view' => 'tdap.dashboard.view',
            ],
            'Prestadores' => [
                'view' => 'tdap.prestadores.view',
                'create' => 'tdap.prestadores.create',
                'edit' => 'tdap.prestadores.edit',
                'delete' => 'tdap.prestadores.delete',
            ],
            'Caminhoes' => [
                'view' => 'tdap.caminhoes.view',
                'create' => 'tdap.caminhoes.create',
                'edit' => 'tdap.caminhoes.edit',
                'delete' => 'tdap.caminhoes.delete',
            ],
            'Atas' => [
                'view' => 'tdap.atas.view',
                'create' => 'tdap.atas.create',
                'edit' => 'tdap.atas.edit',
                'delete' => 'tdap.atas.delete',
            ],
            'Lotes' => [
                'view' => 'tdap.lotes.view',
                'create' => 'tdap.lotes.create',
                'edit' => 'tdap.lotes.edit',
                'delete' => 'tdap.lotes.delete',
            ],
            'Cronogramas' => [
                'view' => 'tdap.cronogramas.view',
                'create' => 'tdap.cronogramas.create',
                'edit' => 'tdap.cronogramas.edit',
                'ativar' => 'tdap.cronogramas.ativar',
                'prorrogar' => 'tdap.cronogramas.prorrogar',
                'export' => 'tdap.cronogramas.export',
            ],
            'Viagens' => [
                'view' => 'tdap.viagens.view',
                'create' => 'tdap.viagens.create',
                'validar' => 'tdap.viagens.validar',
            ],
            'Vistorias' => [
                'view' => 'tdap.vistorias.view',
                'create' => 'tdap.vistorias.create',
                'edit' => 'tdap.vistorias.edit',
                'aprovar' => 'tdap.vistorias.aprovar',
            ],
            'Historico' => [
                'view' => 'tdap.historico.view',
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
        'COMPDEC' => [
            'Orgaos' => [
                'view'   => 'compdec.orgaos.view',
                'create' => 'compdec.orgaos.create',
                'edit'   => 'compdec.orgaos.edit',
                'delete' => 'compdec.orgaos.delete',
                'export' => 'compdec.orgaos.export',
            ],
            'Prefeitura' => [
                'view' => 'compdec.prefeitura.view',
                'edit' => 'compdec.prefeitura.edit',
            ],
            'Equipe' => [
                'view'   => 'compdec.equipe.view',
                'create' => 'compdec.equipe.create',
                'edit'   => 'compdec.equipe.edit',
                'delete' => 'compdec.equipe.delete',
            ],
            'Anexos' => [
                'view'     => 'compdec.anexos.view',
                'create'   => 'compdec.anexos.create',
                'edit'     => 'compdec.anexos.edit',
                'delete'   => 'compdec.anexos.delete',
                'download' => 'compdec.anexos.download',
            ],
            'Plano' => [
                'view'     => 'compdec.plano.view',
                'create'   => 'compdec.plano.create',
                'edit'     => 'compdec.plano.edit',
                'delete'   => 'compdec.plano.delete',
                'aprovar'  => 'compdec.plano.aprovar',
                'download' => 'compdec.plano.download',
            ],
            'UsuarioVinculo' => [
                'manage' => 'compdec.usuarios.manage',
            ],
        ],
        'CISTERNAS' => [
            'Cisternas' => [
                'view'   => 'cisternas.view',
                'create' => 'cisternas.create',
                'edit'   => 'cisternas.edit',
                'delete' => 'cisternas.delete',
                'export' => 'cisternas.export',
            ],
        ],
        'INVENTARIO' => [
            'Equipamentos' => [
                'view'   => 'inventario.equipamentos.view',
                'create' => 'inventario.equipamentos.create',
                'edit'   => 'inventario.equipamentos.edit',
                'delete' => 'inventario.equipamentos.delete',
                'export' => 'inventario.equipamentos.export',
            ],
            'Emprestimos' => [
                'view'     => 'inventario.emprestimos.view',
                'create'   => 'inventario.emprestimos.create',
                'approve'  => 'inventario.emprestimos.approve',
                'return'   => 'inventario.emprestimos.return',
                'export'   => 'inventario.emprestimos.export',
            ],
        ],
        'ESTOQUE' => [
            'Produtos' => [
                'view'   => 'estoque.produtos.view',
                'create' => 'estoque.produtos.create',
                'edit'   => 'estoque.produtos.edit',
                'delete' => 'estoque.produtos.delete',
                'export' => 'estoque.produtos.export',
            ],
            'Lotes' => [
                'view'   => 'estoque.lotes.view',
                'create' => 'estoque.lotes.create',
                'edit'   => 'estoque.lotes.edit',
                'delete' => 'estoque.lotes.delete',
            ],
            'Kits' => [
                'view'        => 'estoque.kits.view',
                'create'      => 'estoque.kits.create',
                'assemble'    => 'estoque.kits.assemble',
                'disassemble' => 'estoque.kits.disassemble',
            ],
            'Movimentacoes' => [
                'view'    => 'estoque.movimentacoes.view',
                'create'  => 'estoque.movimentacoes.create',
                'approve' => 'estoque.movimentacoes.approve',
                'export'  => 'estoque.movimentacoes.export',
            ],
            'Inventarios' => [
                'view'  => 'estoque.inventarios.view',
                'create' => 'estoque.inventarios.create',
                'audit'  => 'estoque.inventarios.audit',
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
            'compdec.*',
            'inventario.*',
            'estoque.*',
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
            // TDAP - gestao completa (sem delete)
            'tdap.dashboard.view',
            'tdap.prestadores.view',
            'tdap.prestadores.create',
            'tdap.prestadores.edit',
            'tdap.caminhoes.view',
            'tdap.caminhoes.create',
            'tdap.caminhoes.edit',
            'tdap.atas.view',
            'tdap.atas.create',
            'tdap.atas.edit',
            'tdap.lotes.view',
            'tdap.lotes.create',
            'tdap.lotes.edit',
            'tdap.cronogramas.view',
            'tdap.cronogramas.create',
            'tdap.cronogramas.edit',
            'tdap.cronogramas.ativar',
            'tdap.cronogramas.prorrogar',
            'tdap.cronogramas.export',
            'tdap.viagens.view',
            'tdap.viagens.create',
            'tdap.viagens.validar',
            'tdap.vistorias.view',
            'tdap.vistorias.create',
            'tdap.vistorias.edit',
            'tdap.vistorias.aprovar',
            'tdap.historico.view',
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
            // COMPDEC - sem delete e sem aprovar
            'compdec.orgaos.view',
            'compdec.orgaos.create',
            'compdec.orgaos.edit',
            'compdec.orgaos.export',
            'compdec.prefeitura.view',
            'compdec.prefeitura.edit',
            'compdec.equipe.view',
            'compdec.equipe.create',
            'compdec.equipe.edit',
            'compdec.equipe.delete',
            'compdec.anexos.view',
            'compdec.anexos.create',
            'compdec.anexos.edit',
            'compdec.anexos.download',
            'compdec.plano.view',
            'compdec.plano.create',
            'compdec.plano.edit',
            'compdec.plano.download',
            'compdec.usuarios.manage',
            // Inventario - gestao completa exceto delete
            'inventario.equipamentos.view',
            'inventario.equipamentos.create',
            'inventario.equipamentos.edit',
            'inventario.equipamentos.export',
            'inventario.emprestimos.view',
            'inventario.emprestimos.create',
            'inventario.emprestimos.approve',
            'inventario.emprestimos.return',
            'inventario.emprestimos.export',
            // Estoque - MVP completo exceto delete
            'estoque.produtos.view',
            'estoque.produtos.create',
            'estoque.produtos.edit',
            'estoque.produtos.export',
            'estoque.lotes.view',
            'estoque.lotes.create',
            'estoque.lotes.edit',
            'estoque.kits.view',
            'estoque.kits.create',
            'estoque.kits.assemble',
            'estoque.kits.disassemble',
            'estoque.movimentacoes.view',
            'estoque.movimentacoes.create',
            'estoque.movimentacoes.approve',
            'estoque.movimentacoes.export',
            'estoque.inventarios.view',
            'estoque.inventarios.create',
            'estoque.inventarios.audit',
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
            // TDAP - view + create em cadastros, view nas operacoes
            'tdap.dashboard.view',
            'tdap.prestadores.view',
            'tdap.prestadores.create',
            'tdap.prestadores.edit',
            'tdap.caminhoes.view',
            'tdap.caminhoes.create',
            'tdap.caminhoes.edit',
            'tdap.atas.view',
            'tdap.lotes.view',
            'tdap.cronogramas.view',
            'tdap.cronogramas.create',
            'tdap.viagens.view',
            'tdap.viagens.create',
            'tdap.vistorias.view',
            'tdap.vistorias.create',
            'tdap.historico.view',
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
            // COMPDEC - sem delete e sem aprovar
            'compdec.orgaos.view',
            'compdec.orgaos.create',
            'compdec.orgaos.edit',
            'compdec.prefeitura.view',
            'compdec.prefeitura.edit',
            'compdec.equipe.view',
            'compdec.equipe.create',
            'compdec.equipe.edit',
            'compdec.anexos.view',
            'compdec.anexos.create',
            'compdec.anexos.edit',
            'compdec.anexos.download',
            'compdec.plano.view',
            'compdec.plano.create',
            'compdec.plano.edit',
            'compdec.plano.download',
            // Inventario - operacao sem delete/aprovacao
            'inventario.equipamentos.view',
            'inventario.equipamentos.create',
            'inventario.equipamentos.edit',
            'inventario.emprestimos.view',
            'inventario.emprestimos.create',
            // Estoque - operacao sem delete/aprovacao
            'estoque.produtos.view',
            'estoque.produtos.create',
            'estoque.produtos.edit',
            'estoque.lotes.view',
            'estoque.lotes.create',
            'estoque.kits.view',
            'estoque.kits.create',
            'estoque.kits.assemble',
            'estoque.movimentacoes.view',
            'estoque.movimentacoes.create',
            'estoque.inventarios.view',
            'estoque.inventarios.create',
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
            // TDAP - view + create de viagens (prestador registra na operaÃ§Ã£o)
            'tdap.dashboard.view',
            'tdap.prestadores.view',
            'tdap.caminhoes.view',
            'tdap.atas.view',
            'tdap.lotes.view',
            'tdap.cronogramas.view',
            'tdap.viagens.view',
            'tdap.viagens.create',
            'tdap.vistorias.view',
            // Treinamento - view
            'treinamento.cursos.view',
            // Plantao - view, create
            'plantao.turnos.view',
            'plantao.turnos.create',
            // BI - view
            'bi.dashboards.view',
            // COMPDEC - leitura + downloads
            'compdec.orgaos.view',
            'compdec.prefeitura.view',
            'compdec.equipe.view',
            'compdec.anexos.view',
            'compdec.anexos.download',
            'compdec.plano.view',
            'compdec.plano.download',
            // Inventario - leitura e solicitacao
            'inventario.equipamentos.view',
            'inventario.emprestimos.view',
            'inventario.emprestimos.create',
            // Estoque - leitura e movimentacao operacional
            'estoque.produtos.view',
            'estoque.lotes.view',
            'estoque.kits.view',
            'estoque.movimentacoes.view',
            'estoque.movimentacoes.create',
        ],
        'viewer' => [
            // Somente visualizacao em todos os modulos
            'pae.empreendimentos.view',
            'pae.protocolos.view',
            'rat.protocolos.view',
            'demandas.chamados.view',
            'decretacoes.processos.view',
            'humanitaria.beneficiarios.view',
            'tdap.dashboard.view',
            'tdap.prestadores.view',
            'tdap.caminhoes.view',
            'tdap.atas.view',
            'tdap.lotes.view',
            'tdap.cronogramas.view',
            'tdap.viagens.view',
            'tdap.vistorias.view',
            'tdap.historico.view',
            'treinamento.cursos.view',
            'plantao.turnos.view',
            'bi.dashboards.view',
            // COMPDEC - somente leitura
            'compdec.orgaos.view',
            'compdec.prefeitura.view',
            'compdec.equipe.view',
            'compdec.anexos.view',
            'compdec.plano.view',
            'inventario.equipamentos.view',
            'inventario.emprestimos.view',
            'estoque.produtos.view',
            'estoque.lotes.view',
            'estoque.kits.view',
            'estoque.movimentacoes.view',
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
            // COMPDEC - apenas listagem de orgaos
            'compdec.orgaos.view',
            // Inventario - acesso inicial ao modulo
            'inventario.equipamentos.view',
            'inventario.emprestimos.view',
            // Estoque - acesso inicial ao modulo
            'estoque.produtos.view',
            'estoque.lotes.view',
            'estoque.kits.view',
            'estoque.movimentacoes.view',
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

