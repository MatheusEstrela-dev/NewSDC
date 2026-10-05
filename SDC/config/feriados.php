<?php

/*
 * Feriados que suspendem o expediente da CEDEC (sede em Belo Horizonte):
 * nacionais, estaduais de MG, municipais de BH e o Carnaval (ponto facultativo
 * do Estado). Usado por App\Support\Calendario\CalendarioDiasUteis.
 *
 * ATUALIZAR TODO ANO. Carnaval (Pascoa - 47), Sexta-feira Santa (Pascoa - 2) e
 * Corpus Christi (Pascoa + 60) sao moveis. Ano ausente faz a contagem de dias
 * uteis tratar o feriado como dia util.
 */
return [
    'datas' => [
        '2025' => [
            '2025-01-01' => 'Confraternizacao Universal',
            '2025-03-03' => 'Carnaval',
            '2025-03-04' => 'Carnaval',
            '2025-04-18' => 'Sexta-feira Santa',
            '2025-04-21' => 'Tiradentes',
            '2025-05-01' => 'Dia do Trabalho',
            '2025-06-19' => 'Corpus Christi (BH)',
            '2025-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2025-09-07' => 'Independencia',
            '2025-10-12' => 'Nossa Senhora Aparecida',
            '2025-11-02' => 'Finados',
            '2025-11-15' => 'Proclamacao da Republica',
            '2025-11-20' => 'Consciencia Negra',
            '2025-12-08' => 'Imaculada Conceicao (BH)',
            '2025-12-25' => 'Natal',
        ],
        '2026' => [
            '2026-01-01' => 'Confraternizacao Universal',
            '2026-02-16' => 'Carnaval',
            '2026-02-17' => 'Carnaval',
            '2026-04-03' => 'Sexta-feira Santa',
            '2026-04-21' => 'Tiradentes',
            '2026-05-01' => 'Dia do Trabalho',
            '2026-06-04' => 'Corpus Christi (BH)',
            '2026-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2026-09-07' => 'Independencia',
            '2026-10-12' => 'Nossa Senhora Aparecida',
            '2026-11-02' => 'Finados',
            '2026-11-15' => 'Proclamacao da Republica',
            '2026-11-20' => 'Consciencia Negra',
            '2026-12-08' => 'Imaculada Conceicao (BH)',
            '2026-12-25' => 'Natal',
        ],
        '2027' => [
            '2027-01-01' => 'Confraternizacao Universal',
            '2027-02-08' => 'Carnaval',
            '2027-02-09' => 'Carnaval',
            '2027-03-26' => 'Sexta-feira Santa',
            '2027-04-21' => 'Tiradentes',
            '2027-05-01' => 'Dia do Trabalho',
            '2027-05-27' => 'Corpus Christi (BH)',
            '2027-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2027-09-07' => 'Independencia',
            '2027-10-12' => 'Nossa Senhora Aparecida',
            '2027-11-02' => 'Finados',
            '2027-11-15' => 'Proclamacao da Republica',
            '2027-11-20' => 'Consciencia Negra',
            '2027-12-08' => 'Imaculada Conceicao (BH)',
            '2027-12-25' => 'Natal',
        ],
    ],
];
