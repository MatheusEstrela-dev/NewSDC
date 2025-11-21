<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuração de Integrações com APIs Externas
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para todas as APIs externas
    | que serão integradas via Saloon. Cada API pode ter sua própria
    | configuração de autenticação e endpoints.
    |
    */

    'apis' => [
        'pae' => [
            'name' => 'API PAE',
            'base_url' => env('API_PAE_BASE_URL', 'https://api-pae.sdc.mg.gov.br'),
            'auth_type' => 'bearer', // bearer, basic, oauth2, api_key
            'token_endpoint' => '/api/auth/token',
            'credentials' => [
                'client_id' => env('API_PAE_CLIENT_ID'),
                'client_secret' => env('API_PAE_CLIENT_SECRET'),
            ],
            'scopes' => ['read', 'write'],
        ],

        'rat' => [
            'name' => 'API RAT',
            'base_url' => env('API_RAT_BASE_URL', 'https://api-rat.sdc.mg.gov.br'),
            'auth_type' => 'bearer',
            'token_endpoint' => '/api/auth/token',
            'credentials' => [
                'client_id' => env('API_RAT_CLIENT_ID'),
                'client_secret' => env('API_RAT_CLIENT_SECRET'),
            ],
            'scopes' => ['read', 'write'],
        ],

        'tdap' => [
            'name' => 'API TDAP',
            'base_url' => env('API_TDAP_BASE_URL', 'https://api-tdap.sdc.mg.gov.br'),
            'auth_type' => 'bearer',
            'token_endpoint' => '/api/auth/token',
            'credentials' => [
                'client_id' => env('API_TDAP_CLIENT_ID'),
                'client_secret' => env('API_TDAP_CLIENT_SECRET'),
            ],
            'scopes' => ['read'],
        ],

        'bi' => [
            'name' => 'API Business Intelligence',
            'base_url' => env('API_BI_BASE_URL', 'https://sdc.mg.gov.br'),
            'auth_type' => 'bearer',
            'token_endpoint' => '/api/auth/token',
            'credentials' => [
                'client_id' => env('API_BI_CLIENT_ID'),
                'client_secret' => env('API_BI_CLIENT_SECRET'),
            ],
            'scopes' => ['read'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuração de Tokens para Power BI
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para geração de tokens únicos
    | que podem ser usados pelo Power BI para acessar múltiplas APIs
    |
    */

    'power_bi' => [
        'enabled' => env('POWER_BI_ENABLED', true),
        'token_ttl' => env('POWER_BI_TOKEN_TTL', 3600), // 1 hora em segundos
        'allowed_apis' => ['pae', 'rat', 'tdap', 'bi'], // APIs permitidas para Power BI
        'default_scopes' => ['read'], // Escopos padrão para Power BI
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache de Tokens
    |--------------------------------------------------------------------------
    |
    | Configuração para cache de tokens gerados, evitando
    | múltiplas requisições desnecessárias
    |
    */

    'token_cache' => [
        'enabled' => env('TOKEN_CACHE_ENABLED', true),
        'ttl' => env('TOKEN_CACHE_TTL', 3300), // 55 minutos (menor que o TTL do token)
        'prefix' => 'api_token_',
    ],
];

