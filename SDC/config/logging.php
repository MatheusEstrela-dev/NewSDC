<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => env('APP_ENV') === 'production'
                ? ['json_stderr', 'daily']
                : ['daily', 'stderr', 'events'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Canal JSON para producao (Docker/Kubernetes/Loki)
        'json_stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [
                PsrLogMessageProcessor::class,
                Monolog\Processor\IntrospectionProcessor::class,
                Monolog\Processor\WebProcessor::class,
                Monolog\Processor\MemoryUsageProcessor::class,
            ],
        ],

        // Canal para logs de containers Docker (stdout para coleta)
        'docker' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'processors' => [
                PsrLogMessageProcessor::class,
                Monolog\Processor\IntrospectionProcessor::class,
                Monolog\Processor\WebProcessor::class,
                Monolog\Processor\MemoryUsageProcessor::class,
                Monolog\Processor\HostnameProcessor::class,
            ],
        ],

        // Canal agregado para producao (Docker + arquivo + critico)
        'production' => [
            'driver' => 'stack',
            'channels' => ['docker', 'daily', 'critical'],
            'ignore_exceptions' => false,
        ],

        // Canal para eventos do sistema (ActivityLogger)
        'events' => [
            'driver' => env('APP_ENV') === 'production' ? 'monolog' : 'daily',
            'path' => storage_path('logs/events.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
            'formatter' => env('APP_ENV') === 'production'
                ? Monolog\Formatter\JsonFormatter::class
                : null,
            'handler' => env('APP_ENV') === 'production'
                ? StreamHandler::class
                : null,
            'with' => [
                'stream' => storage_path('logs/events.log'),
            ],
            'processors' => env('APP_ENV') === 'production'
                ? [PsrLogMessageProcessor::class]
                : [],
        ],

        // Canal para erros criticos (sistema 24/7)
        'critical' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical.log'),
            'level' => 'critical',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // Canal para queries lentas
        'queries' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queries.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // Canal de performance do modulo COMPDEC (QueryThresholdMiddleware)
        'compdec-perf' => [
            'driver' => 'daily',
            'path' => storage_path('logs/compdec-perf.log'),
            'level' => 'warning',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Canal para jobs falhados
        'jobs' => [
            'driver' => 'daily',
            'path' => storage_path('logs/jobs.log'),
            'level' => 'error',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Canal para webhooks (recebimento e envio)
        'webhooks' => [
            'driver' => 'daily',
            'path' => storage_path('logs/webhooks/webhooks.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // Canal para circuit breaker events
        'circuit_breaker' => [
            'driver' => 'daily',
            'path' => storage_path('logs/circuit_breaker.log'),
            'level' => 'warning',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Canal para rate limiting events
        'rate_limit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/rate_limit.log'),
            'level' => 'warning',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'driver' => 'daily',
            'path' => storage_path('logs/emergency.log'),
            'days' => 30,
        ],
    ],

];
