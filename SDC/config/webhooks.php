<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuracoes para processamento de webhooks com resiliencia
    | Seguindo padroes DDD e boas praticas de integracao
    |
    */

    // Numero maximo de tentativas para webhooks falhos
    'max_retries' => env('WEBHOOK_MAX_RETRIES', 4),

    // Backoff exponencial entre tentativas (em segundos)
    // 5s -> 30s -> 5min -> 1h
    'retry_backoff' => array_map(
        'intval',
        explode(',', env('WEBHOOK_RETRY_BACKOFF', '5,30,300,3600'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Configuracoes do circuit breaker para protecao contra falhas em cascata
    |
    */

    'circuit_breaker' => [
        // Numero de falhas antes de abrir o circuito
        'threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5),

        // Tempo em segundos para tentar half-open
        'timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Configuracoes de assinatura HMAC por provider
    |
    */

    'providers' => [
        'github' => [
            'secret' => env('GITHUB_WEBHOOK_SECRET'),
            'algorithm' => 'sha256',
        ],

        'stripe' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'algorithm' => 'sha256',
        ],

        'gitlab' => [
            'secret' => env('GITLAB_WEBHOOK_SECRET'),
            'algorithm' => 'sha256',
        ],

        'default' => [
            'secret' => env('WEBHOOK_SECRET_KEY'),
            'algorithm' => 'sha256',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuracoes de logging para webhooks
    |
    */

    'logging' => [
        // Canal de log para webhooks
        'channel' => 'webhooks',

        // Logar payloads completos (cuidado com dados sensiveis)
        'log_payloads' => env('WEBHOOK_LOG_PAYLOADS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Fila padrao para processamento de webhooks
    |
    */

    'queue' => [
        // Fila para webhooks de entrada
        'inbound' => 'webhooks',

        // Fila para webhooks de saida
        'outbound' => 'webhooks',

        // Fila para webhooks falhos (Dead Letter Queue)
        'dead_letter' => 'dead-letter',
    ],

];
