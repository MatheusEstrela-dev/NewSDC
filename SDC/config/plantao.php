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

    /*
    |---------------------------------------------------------------------------
    | Aceite da passagem de servico
    |---------------------------------------------------------------------------
    */

    'aceite' => [

        // Teto do aviso de "pendente de aceite" quando NAO ha escala publicada
        // que diga quem assume o turno seguinte. Sem escala o sistema nao sabe
        // o destinatario certo e avisa quem pode aceitar -- mas avisar cinquenta
        // pessoas de uma pendencia que e de uma so ensina todo mundo a ignorar
        // o sino.
        'max_destinatarios_fallback' => (int) env('PLANTAO_ACEITE_MAX_DESTINATARIOS', 15),
    ],

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

    /*
    |---------------------------------------------------------------------------
    | Reserva de viatura e QR Code da chave
    |---------------------------------------------------------------------------
    |
    | A retirada da chave exige reserva vigente do proprio agente: o scan sem
    | reserva e recusado. Os numeros abaixo sao a folga operacional que impede
    | essa regra de virar um obstaculo -- o agente que chega cedo consegue a
    | chave, e a reserva que ninguem retirou nao trava a viatura para sempre.
    |
    */

    'reservas' => [

        // Antecedencia com que o check-in e aceito antes do inicio previsto.
        // Sem folga, quem reservou para as 14h e chegou 13h50 seria recusado
        // pela mesma regra que existe para garantir que o carro estivesse la.
        'tolerancia_checkin_minutos' => (int) env('PLANTAO_RESERVA_TOLERANCIA_CHECKIN', 30),

        // Quanto tempo depois do fim previsto a reserva sem check-in vira
        // EXPIRADA. Nao e zero porque o command roda a cada 15 minutos e a
        // pessoa pode estar a caminho; nao e generoso porque cada reserva
        // fantasma bloqueia a agenda da viatura para os outros.
        'expiracao_apos_fim_minutos' => (int) env('PLANTAO_RESERVA_EXPIRACAO_MINUTOS', 60),

        // Teto de duracao de uma reserva. Reserva nao e cessao: passar disso e
        // caso de CEDIDA no cadastro da viatura, com outro tipo de controle.
        'duracao_maxima_horas' => (int) env('PLANTAO_RESERVA_DURACAO_MAXIMA_HORAS', 72),
    ],

];
