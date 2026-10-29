<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Ponto de captacao do cronograma
    |--------------------------------------------------------------------------
    |
    | exige_pmda_aprovado: o cronograma so aceita ponto vinculado, com situacao
    | ATIVO, ao PMDA aprovado mais recente do municipio. Pontos que o cronograma
    | ja tinha antes da regra continuam aceitos na edicao (acervo legado).
    |
    | Desligada, volta a regra anterior: qualquer ponto do municipio. Existe
    | para recuar sem deploy enquanto os municipios cadastram os pontos no PMDA
    | -- em 2026-10-02 havia 1 vinculo plano<->ponto para 220 pontos.
    |
    */
    'ponto_captacao' => [
        'exige_pmda_aprovado' => (bool) env('TDAP_PONTO_EXIGE_PMDA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ordem das decisoes sobre a viagem
    |--------------------------------------------------------------------------
    |
    | exige_confirmacao_municipio: a CEDEC so aprova para pagamento depois que
    | o municipio confirmou o recebimento, e o municipio deixa de decidir
    | sobre viagem que a CEDEC ja validou. Sem isso o COMPDEC podia reprovar
    | uma viagem ja paga, e a reprovacao nao desfazia o pagamento.
    |
    | Desligada, a CEDEC aprova sem esperar o municipio. Existe para recuar sem
    | deploy: municipio sem usuario COMPDEC, ou cronograma ja fora do limite
    | (LimiteDoCronograma barra a decisao municipal), deixam a viagem sem
    | ninguem que possa confirma-la.
    |
    */
    'viagens' => [
        'exige_confirmacao_municipio' => (bool) env('TDAP_VIAGEM_EXIGE_CONFIRMACAO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tramitacao do cronograma
    |--------------------------------------------------------------------------
    |
    | exige_tramite: o cronograma so e ativado depois de passar pela analise
    | da CEDEC e chegar a fase `aprovado` (FaseCronograma). O botao "Ativar"
    | da ficha passa pela mesma regra.
    |
    | Desligada, a ativacao volta a ser direta a partir do rascunho, como era
    | antes do Acompanhamento. Existe para recuar sem deploy.
    |
    */
    'cronogramas' => [
        'exige_tramite' => (bool) env('TDAP_CRONOGRAMA_EXIGE_TRAMITE', true),

        /*
        | segregacao_funcoes: ninguem confere o proprio trabalho -- quem enviou
        | para analise nao aprova, quem aprovou nao ativa (CronogramaWorkflow).
        | Desligar so para equipe de uma pessoa, sem deploy.
        */
        'segregacao_funcoes' => (bool) env('TDAP_CRONOGRAMA_SEGREGACAO', true),
    ],

];
