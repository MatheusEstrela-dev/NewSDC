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

];
