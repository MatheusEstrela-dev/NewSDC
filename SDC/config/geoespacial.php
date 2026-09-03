<?php

declare(strict_types=1);

return [
    // Dominio novo entra aqui, sem migration: o que varia entre eles e legenda
    // e vocabulario, nao estrutura. A geometria e a mesma coluna.
    'dominios' => [
        'geologico' => [
            'rotulo' => 'Geologico',
            'cor' => '#b45309',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
        'hidro' => [
            'rotulo' => 'Hidrologico',
            'cor' => '#1d4ed8',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
        'meteorologico' => [
            'rotulo' => 'Meteorologico',
            'cor' => '#7c3aed',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
    ],

    // Limite do upload, em KB, aplicado no FormRequest.
    'upload_max_kb' => (int) env('GEOESPACIAL_UPLOAD_MAX_KB', 20480),
];
