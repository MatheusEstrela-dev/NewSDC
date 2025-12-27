<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAPI Validation
    |--------------------------------------------------------------------------
    |
    | Configurações para validação automática de requisições contra o spec OpenAPI.
    | O middleware ValidateOpenApiRequest usa estas configurações.
    |
    */

    'openapi' => [
        // Ativa/desativa validação OpenAPI
        'enabled' => env('OPENAPI_VALIDATION_ENABLED', true),

        // Caminho do arquivo de spec
        'spec_path' => env('OPENAPI_SPEC_PATH', storage_path('api-docs/api-docs.json')),

        // TTL do cache do spec (em segundos)
        'cache_ttl' => env('OPENAPI_CACHE_TTL', 3600),

        // Se deve falhar quando spec não é encontrado (true) ou continuar (false)
        'fail_on_missing_spec' => env('OPENAPI_FAIL_ON_MISSING', false),

        // Paths que não devem ser validados
        'skip_paths' => [
            'api/documentation',
            'api/health',
            'sanctum/csrf-cookie',
            '_debugbar',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Configurações para o middleware de idempotência.
    | Previne duplicatas em requisições não-safe (POST, PUT, PATCH, DELETE).
    |
    */

    'idempotency' => [
        // Ativa/desativa idempotência
        'enabled' => env('IDEMPOTENCY_ENABLED', true),

        // TTL do cache de respostas (em segundos) - 24 horas padrão
        'cache_ttl' => env('IDEMPOTENCY_TTL', 86400),

        // Se deve validar estritamente UUID v4 (true) ou aceitar qualquer string (false)
        'strict_uuid' => env('IDEMPOTENCY_STRICT_UUID', true),

        // Timeout do lock (em segundos) - para evitar race conditions
        'lock_timeout' => env('IDEMPOTENCY_LOCK_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configurações para rate limiting contextual.
    | Considera plano do usuário + custo da rota.
    |
    */

    'rate_limit' => [
        // Ativa/desativa rate limiting
        'enabled' => env('RATE_LIMIT_ENABLED', true),

        // Usa Redis para rate limiting (recomendado para produção)
        'use_redis' => env('RATE_LIMIT_USE_REDIS', true),

        // Custos de créditos por tipo de rota
        'route_costs' => [
            'heavy' => env('RATE_LIMIT_COST_HEAVY', 10),      // export, relatórios
            'expensive' => env('RATE_LIMIT_COST_EXPENSIVE', 5), // dashboard, analytics
            'normal' => env('RATE_LIMIT_COST_NORMAL', 1),       // CRUD padrão
            'light' => env('RATE_LIMIT_COST_LIGHT', 0.5),       // health, status
        ],

        // Limites por tier (créditos por minuto)
        'tiers' => [
            'public' => [
                'max_attempts' => env('RATE_LIMIT_PUBLIC', 60),
                'decay_seconds' => 60,
            ],
            'free' => [
                'max_attempts' => env('RATE_LIMIT_FREE', 300),
                'decay_seconds' => 60,
            ],
            'pro' => [
                'max_attempts' => env('RATE_LIMIT_PRO', 1000),
                'decay_seconds' => 60,
            ],
            'enterprise' => [
                'max_attempts' => env('RATE_LIMIT_ENTERPRISE', 10000),
                'decay_seconds' => 60,
            ],
            'webhook' => [
                'max_attempts' => env('RATE_LIMIT_WEBHOOK', 50000),
                'decay_seconds' => 60,
            ],
            'admin' => [
                'max_attempts' => env('RATE_LIMIT_ADMIN', 100000),
                'decay_seconds' => 60,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Processing
    |--------------------------------------------------------------------------
    |
    | Configurações para processamento assíncrono (Fire & Forget).
    |
    */

    'async' => [
        // Tempo estimado padrão de processamento (em segundos)
        'default_estimated_seconds' => env('ASYNC_DEFAULT_ESTIMATED_SECONDS', 30),

        // Filas disponíveis
        'queues' => [
            'high_throughput' => env('QUEUE_HIGH_THROUGHPUT', 'high-throughput'),
            'normal' => env('QUEUE_NORMAL', 'default'),
            'low' => env('QUEUE_LOW', 'low'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Observability
    |--------------------------------------------------------------------------
    |
    | Configurações para logs e observabilidade do sistema de API.
    |
    */

    'logging' => [
        // Loga todas as validações OpenAPI que falharam
        'log_validation_failures' => env('API_LOG_VALIDATION_FAILURES', true),

        // Loga todas as requisições que excederam o rate limit
        'log_rate_limit_exceeded' => env('API_LOG_RATE_LIMIT_EXCEEDED', true),

        // Loga todas as requisições idempotentes cacheadas
        'log_idempotency_hits' => env('API_LOG_IDEMPOTENCY_HITS', true),
    ],

];
