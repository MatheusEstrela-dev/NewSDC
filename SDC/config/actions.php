<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modulos com Configuracao de Acoes
    |--------------------------------------------------------------------------
    |
    | Lista de classes que implementam ModuleActionsInterface.
    | Cada classe define as acoes disponiveis para seu modulo.
    |
    */
    'modules' => [
        \App\Modules\Rat\Config\RatActionsConfig::class,
        \App\Modules\Pae\Config\PaeActionsConfig::class,
        \App\Modules\Compdec\Config\OrgaoActionsConfig::class,
        \App\Modules\Decretacoes\Config\DecretacoesActionsConfig::class,
        \App\Modules\AjudaHumanitaria\Config\AjudaHumanitariaActionsConfig::class,
    ],
];
