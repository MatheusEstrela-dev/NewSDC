<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Redis habilitado
    |--------------------------------------------------------------------------
    |
    | Liga/desliga os componentes que dependem de Redis cru (ConnectionSemaphore,
    | ApiRateLimiter e bucket global). Quando false, esses componentes degradam
    | graciosamente (no-op / fail-open), permitindo a aplicacao operar sem Redis.
    | Em prod sem Redis defina REDIS_ENABLED=false.
    */

    'redis_enabled' => filter_var(env('REDIS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | DB Pool e Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Knobs para o semáforo de conexões em Redis (ConnectionSemaphore) e
    | para o DatabaseCircuitBreaker que observa timeouts via DB::listen.
    | Ver: docs/superpowers/specs/2026-05-20-postgres-connection-pooling-design.md
    */

    'db' => [
        // Limite de requests concorrentes que podem acessar DB simultaneamente.
        // Excedido, novas requests entram em fila no semáforo até acquire_wait_ms.
        'max_concurrent' => (int) env('DB_MAX_CONCURRENT', 100),

        // Limite separado para jobs de webhook (conexão pgsql_webhook).
        'max_concurrent_webhook' => (int) env('DB_MAX_CONCURRENT_WEBHOOK', 20),

        // Tempo máximo (ms) que o semáforo espera por slot livre antes de retornar 503.
        'acquire_wait_ms' => (int) env('DB_ACQUIRE_WAIT_MS', 2000),

        // Intervalo de polling (ms) entre tentativas de acquire.
        'acquire_poll_ms' => (int) env('DB_ACQUIRE_POLL_MS', 50),

        'circuit_breaker' => [
            // p95 de duração de query (ms) que sinaliza degradação. Acima disso, contabiliza.
            'p95_threshold_ms' => (int) env('DB_CB_P95_MS', 500),

            // Quantos timeouts em window_seconds disparam abertura do circuito.
            'timeout_count_threshold' => (int) env('DB_CB_TIMEOUT_COUNT', 5),

            // Janela de observação (segundos) para contagem de timeouts.
            'window_seconds' => (int) env('DB_CB_WINDOW_S', 60),

            // Após abrir, tempo (segundos) até tentar half-open.
            'reset_timeout_seconds' => (int) env('DB_CB_RESET_S', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        // Threshold do bucket global (requests/segundo). Acima disso, tiers
        // public/free/default recebem 503; internal/admin/webhook continuam.
        'global_per_second' => (int) env('RATE_LIMIT_GLOBAL', 1500),

        // Se Redis falhar, true = derruba tiers de baixa prioridade (seguro);
        // false = permite tudo (legado, não recomendado em prod).
        'fail_closed' => (bool) env('RATE_LIMIT_FAIL_CLOSED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Budget (detecção de N+1)
    |--------------------------------------------------------------------------
    */

    'query_budget' => [
        // Acima desse número de queries por request, loga warning (suspeita N+1).
        'warn_at' => (int) env('QUERY_BUDGET_WARN', 30),

        // Acima desse número, loga critical (N+1 confirmado).
        'fail_at' => (int) env('QUERY_BUDGET_FAIL', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backpressure (drop não-crítico sob carga)
    |--------------------------------------------------------------------------
    |
    | Thresholds são fração de utilização do semáforo (0.0 a 1.0).
    | Range válido: 0.0–1.0. Valores fora dessa faixa serão tratados pelo
    | middleware consumer (Backpressure) — ver Task 7 do plano.
    */

    'backpressure' => [
        // Acima de X% de utilização do semáforo, tier public é dropado.
        'public_drop_threshold' => (float) env('BP_PUBLIC_DROP', 0.7),

        // Acima de X% de utilização, tier free/default é dropado.
        'free_drop_threshold' => (float) env('BP_FREE_DROP', 0.9),
    ],
];
