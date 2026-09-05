<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Conexao do Banco Legado gestaocedec (ETL de prefeituras)
    |--------------------------------------------------------------------------
    | Nome da connection, definida em config/database.php, que
    | PrefeituraService::migrarLegado() usa para ler cedec_municipio e
    | cedec_prefeitura.
    |
    | Dedicada de proposito: NAO reutiliza compdec.legacy_connection ('legacy'),
    | que aponta para dbsdc, o banco do legado sdc/Laravel. Os dois bancos tem
    | tabelas de mesmo nome e schema incompativel, e a conexao 'legacy' e
    | compartilhada por sete consumidores em Compdec, Pmda e AjudaHumanitaria.
    |
    | Ver docs/superpowers/plans/2026-09-04-cedec-fase1-dados-etl.md, Task 1.
    */
    'legacy_connection' => env('CEDEC_LEGACY_CONNECTION', 'legado_gestaocedec'),
];
