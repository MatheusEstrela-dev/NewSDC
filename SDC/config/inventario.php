<?php

declare(strict_types=1);

/*
| Integracoes do Inventario de TI. Nada de endereco, IP ou nome fixo no codigo:
| o assunto do chamado e os destinatarios da SEPLAG mudam por ambiente.
*/

$destinatarios = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('INVENTARIO_SEPLAG_DESTINATARIOS', '')),
)));

return [
    'remanejamento' => [
        // Nome EXATO de um assunto de Demandas. Sem ele, "Registrar chamado"
        // responde com erro de configuracao e nao grava nada.
        'assunto_chamado' => env('INVENTARIO_ASSUNTO_CHAMADO_LOTE'),
    ],

    'seplag' => [
        'destinatarios' => $destinatarios,
        'assunto_email' => env('INVENTARIO_SEPLAG_ASSUNTO', 'Desbloqueio de pontos de rede - remanejamento'),
    ],
];
