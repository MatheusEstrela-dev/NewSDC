<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            // Inclui certificados de 300s ja enfileirados na fila default.
            'retry_after' => 360,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // ProcessIntegration pode usar qualquer prioridade e tem timeout=120.
        // A reserva considera o timeout do job, que vence o --timeout do worker.
        // Fila critica - maxima prioridade.
        'redis-critical' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'critical',
            'retry_after' => 180,
            'block_for' => 2,
            'after_commit' => false,
        ],

        // Fila alta - segunda prioridade
        'redis-high' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'high',
            'retry_after' => 180,
            'block_for' => 3,
            'after_commit' => false,
        ],

        // Fila de webhooks
        'redis-webhooks' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'webhooks',
            'retry_after' => 180,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // Fila baixa - processamento em background.
        // retry_after > maior timeout de job consumido nesta fila (export jobs
        // assincronos tem timeout=600s) para evitar re-enfileiramento duplicado
        // do mesmo job enquanto ainda executa.
        'redis-low' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'low',
            'retry_after' => 660,
            'block_for' => 10,
            'after_commit' => false,
        ],

        // Auditoria: fila propria, separada da 'low'.
        //
        // O RecordActivityLog e de longe o job mais frequente do sistema (ate um
        // por requisicao) e o mais barato de executar. Compartilhando a fila
        // 'low' com os certificados (timeout 600s) e um unico consumidor, um
        // certificado de dez minutos PARAVA a auditoria inteira atras dele e o
        // backlog crescia na memoria do Redis. Sao perfis opostos -- muitos jobs
        // curtos contra poucos jobs longos -- e nao podem dividir consumidor.
        //
        // retry_after curto porque o job e curto: nao ha motivo para uma linha
        // de auditoria presa por 11 minutos como acontecia na 'low'.
        'redis-auditoria' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'auditoria',
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // O consumidor deve selecionar esta conexao: --queue sozinho nao
        // altera retry_after. O maior job do ETL tem timeout de 900 segundos.
        'redis-medalhao' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'medalhao',
            'retry_after' => 960,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // Dead Letter Queue - webhooks falhos para analise manual
        'redis-ranking' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('RANKING_FILA', 'ranking'),
            'retry_after' => 180,
            'block_for' => 5,
            'after_commit' => true,
        ],

        'dead-letter' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'dead-letter',
            'retry_after' => 86400,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
