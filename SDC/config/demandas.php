<?php

declare(strict_types=1);

return [
    'anexos' => [
        'disk' => env('DEMANDAS_ANEXOS_DISK', 'local'),
        // Mesma lista do cedec-demanda (AnexoRequest), mais jpeg.
        'mimes' => ['png', 'jpg', 'jpeg', 'pdf', 'xlsx', 'xls', 'csv', 'txt'],
        'max_kb' => 10240,
    ],

    'importacao' => [
        'conexao' => env('DEMANDAS_LEGADO_CONEXAO', 'cedec_demanda_legacy'),
        'disk' => env('DEMANDAS_LEGADO_DISK', 'legado_demandas'),
        'lote' => 200,
    ],

    'automacao' => [
        'timeout_segundos' => 15,
        'tentativas' => 2,
        'backoff_segundos' => [10, 30],
    ],
];
