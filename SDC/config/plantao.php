<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Rodape do relatorio de passagem de servico
    |---------------------------------------------------------------------------
    |
    | Constantes operacionais, nao dado de turno. Vivem em config para que um
    | telefone possa ser corrigido sem tocar em template nem em service. Na
    | release do painel de postos organicos, os contatos passam a ser dado
    | gerenciado em banco.
    |
    */

    'relatorio' => [
        'rodape' => [
            'contatos_diesel' => [
                '3 BBM: 031 3490-5531',
                '3a Cia PE - Santa Luzia: 031 3268-0958 / 031 2138-5700',
                '1 BBM: 031 3289-8073',
                '40 BPM: 031 3036-0750',
                '5 BPM: 031 2123-1167',
            ],
            'link_bi' => env(
                'PLANTAO_LINK_BI_COMBUSTIVEL',
                'https://app.powerbi.com/view?r=eyJrIjoiN2RhYjQ3N2MtMDAxOC00YmI4LThjNGYtMjZiMjE0OWNjZGQ0IiwidCI6ImU1ZDNhZTdjLTliMzgtNDhkZS1hMDg3LWY2NzM0YTI4NzU3NCJ9'
            ),
            'dtt' => 'saida de viaturas de Segunda a Sexta de 06:00 as 22:00 - Tel. (31)-9-9826-2400 / 3915-4718',
            'gmg' => 'saida de viaturas CEDEC final de semana e feriados - Tel. (31) 9-9382-6023.',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Escala de plantao
    |---------------------------------------------------------------------------
    |
    | Regras operacionais do planejamento de turnos. Em config, e nao em banco,
    | porque sao politica da corporacao e nao dado do dia a dia -- mudam por
    | decisao, nao por operacao.
    |
    */

    'escala' => [

        // Quantos minutos antes do inicio do turno o plantonista e lembrado.
        // O comando roda a cada 15 minutos e varre a janela
        // [agora, agora + lembrete_minutos_antes]; itens ja avisados sao
        // filtrados por lembrete_enviado_em, nunca reenviados.
        'lembrete_minutos_antes' => (int) env('PLANTAO_ESCALA_LEMBRETE_MINUTOS', 120),

        // Descanso minimo recomendado entre dois turnos do mesmo plantonista.
        // AVISA, nao bloqueia: emenda acontece e as vezes e inevitavel -- o
        // sistema registra que foi consciente, em vez de impedir a operacao.
        'intervalo_minimo_horas' => (int) env('PLANTAO_ESCALA_INTERVALO_MINIMO_HORAS', 8),

        // Sobreposicao de horario do mesmo plantonista BLOQUEIA. Nao ha leitura
        // valida de estar em dois turnos ao mesmo tempo, e o indice unico do
        // banco nao pega o caso (tipos de turno diferentes).
        'bloquear_sobreposicao' => true,
    ],

];
