<?php

declare(strict_types=1);

use App\Modules\Ranking\Enums\FaixaRanking;

return [
    // Laboratorio de homologacao: somente catalogo e calculo puro, sem lancamentos.
    'preview' => (bool) env('RANKING_PREVIEW', false),
    // Interruptor geral. Desligado, o modulo nao consome eventos e o placar
    // fica oculto; o livro e os saldos ja gravados permanecem intactos, que e
    // o comportamento esperado em rollback (suspender a vitrine, nao apagar a
    // prova).
    'habilitado' => (bool) env('RANKING_HABILITADO', false),

    // Modo sombra: consome eventos, decide e grava o livro normalmente, mas o
    // placar nao e exposto. Usado para validar a captura contra jornadas reais
    // antes de publicar posicao para os usuarios.
    'modo_sombra' => (bool) env('RANKING_MODO_SOMBRA', true),

    // Schema fixo dentro da database independente sdc_ranking.
    // Nunca reutilizar a conexao operacional como fallback.
    'schema' => 'ranking',
    'conexao' => 'ranking',
    'conexao_origem' => 'ranking_source_ro',
    'conexao_leitura' => 'ranking_read',

    'fila' => [
        // Fila propria para que o processamento de pontos nunca dispute worker
        // com notificacao ou webhook. Vide docker/supervisor/.
        'nome' => env('RANKING_FILA', 'ranking'),
        'tentativas' => (int) env('RANKING_FILA_TENTATIVAS', 3),
        // Mesmo limite do listener e do supervisor; retry_after da conexao e maior.
        'timeout' => 120,
        'backoff_segundos' => [10, 30, 60],
    ],

    'pontuacao' => [
        // Percentual do bonus de tempestividade, aplicado sobre a base com
        // divisao inteira: floor(base * 20 / 100). So incide quando o prazo e
        // conhecido E foi cumprido; prazo ausente ou desconhecido nao bonifica
        // e tambem nao penaliza.
        'bonus_percentual' => (int) env('RANKING_BONUS_PERCENTUAL', 20),

        // Teto de seguranca por lancamento. Nao e regra de negocio: e freio
        // contra regra mal cadastrada ou adapter defeituoso gerando credito
        // absurdo. Estouro vira em_apuracao, nunca credito silencioso.
        'teto_por_lancamento' => (int) env('RANKING_TETO_LANCAMENTO', 500),
    ],

    // Limiares das faixas. Fonte de verdade e o enum FaixaRanking; este bloco
    // existe para a UI e para relatorio, e e verificado contra o enum pelo
    // comando ranking:verify-catalog.
    'faixas' => [
        FaixaRanking::Bronze->value => ['min' => 0, 'max' => 299],
        FaixaRanking::Prata->value => ['min' => 300, 'max' => 699],
        FaixaRanking::Ouro->value => ['min' => 700, 'max' => 1499],
        FaixaRanking::Diamante->value => ['min' => 1500, 'max' => null],
    ],

    'placar' => [
        'por_pagina' => (int) env('RANKING_POR_PAGINA', 25),

        // TTL curto: o placar e alimentado por evento e muda a qualquer
        // momento. A chave de cache inclui escopo, periodo, modulo e geracao -
        // autorizacao nunca entra no cache.
        'cache_segundos' => (int) env('RANKING_CACHE_SEGUNDOS', 60),
    ],

    // Marcos habilitados. Um marco so entra aqui depois que o modulo de origem
    // emite o evento correspondente com autoria comprovada no metadata. Enquanto
    // nao emitir, fica registrado como lacuna visivel em ranking:verify-catalog
    // com o motivo, em vez de sumir do catalogo.
    //
    // Motivos aceitos: workflow_not_available | source_evidence_missing
    'marcos' => [
        // Fase 4 liga estes dois modulos.
        'rat' => env('RANKING_MARCOS_RAT', false),
        'pae' => env('RANKING_MARCOS_PAE', false),
    ],

    // Modulos que nao concedem premio pelo proprio processamento. Registrados
    // explicitamente para que "sem regra" seja decisao documentada e nao
    // esquecimento - ingestao automatica e infraestrutura nao sao entrega de
    // usuario.
    'modulos_sem_premio' => [
        'Cemaden' => 'ingestao_automatica',
        'Inmet' => 'ingestao_automatica',
        'Sismos' => 'evento_natural_nao_e_entrega',
        'Medalhao' => 'infraestrutura_de_dados',
        'Notificacoes' => 'nao_multiplica_transacao_de_origem',
        'Dashboard' => 'apenas_consome_placar',
        'Shared' => 'infraestrutura_transversal',
        'Ranking' => 'nao_pontua_o_proprio_processamento',
    ],
];
