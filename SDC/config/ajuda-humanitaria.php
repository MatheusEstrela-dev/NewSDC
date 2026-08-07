<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de anexos
    |--------------------------------------------------------------------------
    | Onde os PDFs anexados ao pedido sao gravados (RN-22). Mesmo padrao do
    | modulo Compdec, que mantem disco proprio por modulo.
    */
    'disk' => env('AJUDA_HUMANITARIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Limites de upload, em bytes
    |--------------------------------------------------------------------------
    | RN-22: o legado aceita apenas PDF, no maximo 2 MB.
    */
    'upload_limits' => [
        'anexo_pedido' => 2 * 1024 * 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conexao com a base legada
    |--------------------------------------------------------------------------
    | Usada somente para leitura de saldo de material por deposito (RN-25).
    | Aponta para a base do sistema procedural (gestaocedec), onde vivem
    | aju_estoque, aju_deposito e aju_unidade, e nao para o port Laravel.
    |
    | Enquanto as chaves DB_LEGACY_* nao estiverem preenchidas no .env, a ponte
    | reporta saldo indisponivel em vez de estourar.
    */
    'legacy_connection' => env('AJUDA_HUMANITARIA_LEGACY_CONNECTION', 'legacy'),

    /*
    |--------------------------------------------------------------------------
    | Cache do saldo legado, em segundos
    |--------------------------------------------------------------------------
    | O saldo muda com baixa frequencia e a consulta cruza tres tabelas em outro
    | banco. Cache curto evita martelar a base legada a cada abertura de tela.
    */
    'saldo_cache_ttl' => (int) env('AJUDA_HUMANITARIA_SALDO_CACHE_TTL', 300),

];
