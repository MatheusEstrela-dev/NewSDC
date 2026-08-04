<?php

declare(strict_types=1);

/**
 * Configuracao do inbox de notificacoes do usuario final.
 *
 * Esta e a fonte unica da lista de modulos que emitem notificacao. Antes, a lista
 * vivia duplicada em tres lugares (CHECK constraint no banco, const MODULES no
 * model e array hardcoded no SettingsModal.vue). Adicionar um modulo agora e
 * uma linha aqui, sem migration e sem tocar no frontend.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Modulos notificaveis
    |--------------------------------------------------------------------------
    |
    | O slug e a chave persistida em user_notification_preferences.module e o
    | prefixo esperado no group_key ("rat:123"). label e descricao alimentam a
    | tela de preferencias; janela e o tempo de agrupamento em minutos, que cai
    | no valor de agrupamento.janela_padrao_minutos quando omitido.
    |
    | nome_curto e como o protocolo aparece no TEXTO da notificacao ("RAT
    | atualizado", "Abrir RAT"). Fica aqui, e nao no model, para o vocabulario que
    | chega ao usuario final ter um lugar so -- mesmo motivo de label e icone.
    |
    */
    'modulos' => [
        'rat' => [
            'label' => 'Relatorios (RAT)',
            'nome_curto' => 'RAT',
            'descricao' => 'Alertas sobre novos relatorios, vistorias e aprovacoes.',
            'icone' => 'DocumentTextIcon',
        ],
        'pae' => [
            'label' => 'Planos (PAE)',
            'nome_curto' => 'Protocolo PAE',
            'descricao' => 'Vencimentos de prazos e atualizacoes de status.',
            'icone' => 'MapIcon',
            // Prazo vencendo nao deve ser agrupado: cada protocolo importa.
            'janela' => 0,
        ],
        'meteorologia' => [
            'label' => 'Meteorologia',
            'nome_curto' => 'Alerta',
            'descricao' => 'Alertas criticos de chuva e mudancas climaticas (INMET).',
            'icone' => 'CloudIcon',
            // Alerta climatico repete muito em sequencia; janela mais larga.
            'janela' => 60,
        ],
        'demandas' => [
            'label' => 'Demandas/Chamados',
            'nome_curto' => 'Demanda',
            'descricao' => 'Atribuicoes de tarefas e novos comentarios.',
            'icone' => 'CheckBadgeIcon',
        ],
        'decretacoes' => [
            'label' => 'Decretacoes',
            'nome_curto' => 'Processo',
            'descricao' => 'Movimentacoes em decretos e reconhecimentos.',
            'icone' => 'DocumentTextIcon',
        ],
        'tdap' => [
            'label' => 'TDAP',
            'nome_curto' => 'Processo TDAP',
            'descricao' => 'Recebimentos, saidas de estoque e cronogramas.',
            'icone' => 'TruckIcon',
        ],
        'plancon' => [
            'label' => 'PlanCon',
            'nome_curto' => 'Plano de contingencia',
            'descricao' => 'Planos de contingencia e prazos de revisao.',
            'icone' => 'ClipboardDocumentListIcon',
        ],
        'pmda' => [
            'label' => 'PMDA',
            'nome_curto' => 'Plano PMDA',
            'descricao' => 'Planos municipais, COMPDEC e solicitacoes de comunidades.',
            'icone' => 'BuildingLibraryIcon',
        ],
        'compdec' => [
            'label' => 'COMPDEC',
            'nome_curto' => 'Orgao',
            'descricao' => 'Cadastro, vigencia e composicao das coordenadorias.',
            'icone' => 'UserGroupIcon',
        ],
        'estoque' => [
            'label' => 'Estoque',
            'nome_curto' => 'Item de estoque',
            'descricao' => 'Movimentacoes, saldo minimo e vencimento de itens.',
            'icone' => 'ArchiveBoxIcon',
        ],
        'inventario' => [
            'label' => 'Inventario',
            'nome_curto' => 'Item de inventario',
            'descricao' => 'Patrimonio, conferencias e baixas.',
            'icone' => 'ArchiveBoxIcon',
        ],
        'ajuda-humanitaria' => [
            'label' => 'Ajuda Humanitaria',
            'nome_curto' => 'Auxilio',
            'descricao' => 'Pedidos, doacoes e distribuicoes.',
            'icone' => 'HeartIcon',
        ],
        'plantao' => [
            'label' => 'Plantao',
            'nome_curto' => 'Plantao',
            'descricao' => 'Escalas, trocas e acionamentos.',
            'icone' => 'ClockIcon',
        ],
        'treinamento' => [
            'label' => 'Treinamento',
            'nome_curto' => 'Treinamento',
            'descricao' => 'Inscricoes, turmas e certificados.',
            'icone' => 'AcademicCapIcon',
        ],
        'suporte' => [
            'label' => 'Suporte',
            'nome_curto' => 'Ticket',
            'descricao' => 'Respostas e andamento dos seus tickets.',
            'icone' => 'LifebuoyIcon',
        ],
        'cisterna' => [
            'label' => 'Cisternas',
            'nome_curto' => 'Cisterna',
            'descricao' => 'Solicitacoes, vistorias e entregas.',
            'icone' => 'BeakerIcon',
        ],
        'geral' => [
            'label' => 'Geral',
            'nome_curto' => 'Aviso',
            'descricao' => 'Exportacoes, processamentos em segundo plano e avisos do sistema.',
            'icone' => 'BellIcon',
            // Conclusao de export e evento unico do proprio usuario: nao agrupa.
            'janela' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agrupamento
    |--------------------------------------------------------------------------
    |
    | Enquanto uma notificacao com o mesmo group_key nao for lida e a janela nao
    | expirar, novos eventos incrementam group_count na mesma linha em vez de
    | criar linhas novas. Janela 0 desliga o agrupamento para o modulo.
    |
    */
    'agrupamento' => [
        'janela_padrao_minutos' => 15,

        // Lock curto no Redis para que dois eventos simultaneos nao criem duas
        // linhas abertas para o mesmo assunto (papiro: stampede protection).
        'lock_segundos' => 5,
        'lock_espera_segundos' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbox
    |--------------------------------------------------------------------------
    |
    | Limites do payload do sino. O historico completo tem pagina propria e nao
    | usa esses limites.
    |
    */
    'inbox' => [
        // Quantos cards o sino mostra. O painel e uma previa, nao a lista completa:
        // quem quer ver tudo usa o botao de historico. O badge continua contando
        // TODAS as nao lidas, e nao apenas as exibidas aqui.
        'painel_max' => 4,

        'historico_por_pagina' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contador de nao lidas
    |--------------------------------------------------------------------------
    |
    | Cache do badge do sino, lido em toda navegacao via share do Inertia.
    |
    */
    'contador' => [
        'prefixo' => 'notif:unread:',
        'tag' => 'notificacoes',
        'ttl_segundos' => 3600,
        'lock_segundos' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entrega
    |--------------------------------------------------------------------------
    |
    | Fan-out em lotes: os destinatarios sao resolvidos dentro do job ja
    | enfileirado e notificados em blocos, evitando um job por (usuario x canal).
    | A fila segue a lista de prioridade do worker em producao.
    |
    */
    'entrega' => [
        'chunk_destinatarios' => 200,
        'fila' => 'default',
        'fila_urgente' => 'high',
        'tentativas' => 3,
        'backoff_segundos' => [10, 30, 60],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retencao
    |--------------------------------------------------------------------------
    |
    | Nada e apagado: passados os dias abaixo, a notificacao sai de notifications
    | e vai para notifications_archive (comando notificacoes:arquivar, agendado em
    | app/Console/Kernel.php). Mesma tratativa do webhooks:archive.
    |
    | A tabela operacional fica pequena, o que mantem o badge e o inbox rapidos, e
    | o historico continua auditavel no arquivo.
    |
    */
    'retencao' => [
        'dias_para_arquivar' => 90,
    ],

];
