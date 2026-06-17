<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Swoole Server Options
    |--------------------------------------------------------------------------
    |
    | Opcoes passadas ao Swoole quando OCTANE_SERVER=swoole. Coroutines com
    | SWOOLE_HOOK_ALL tornam Eloquent/PDO e predis (PHP puro) assincronos sem
    | reescrita. IMPORTANTE: DB_PERSISTENT deve ser false sob hook (PDO
    | persistente compartilhado entre coroutines quebra). Task workers isolam
    | CPU pesada (hash) do pool HTTP. enable_reuse_port elimina o lock de
    | accept(); http_compression reduz I/O de rede em respostas grandes.
    |
    */

    'swoole' => [
        'options' => [
            'enable_coroutine' => true,
            // Fase 3 (RedisPool nativo) pendente: sob SWOOLE_HOOK_ALL as conexoes
            // Redis (sessao+cache+permissoes, usadas em todo request) sao
            // compartilhadas entre coroutines e colidem ("Socket already bound to
            // another coroutine"). Ate o pool existir, os hooks ficam OFF por
            // padrao -> I/O bloqueante por worker, sem interleaving = estavel.
            // Reabilitar com OCTANE_HOOK_FLAGS_ENABLED=true quando o pool entrar.
            'hook_flags' => env('OCTANE_HOOK_FLAGS_ENABLED', false) && defined('SWOOLE_HOOK_ALL') ? SWOOLE_HOOK_ALL : 0,
            'task_worker_num' => (int) env('OCTANE_TASK_WORKERS', 4),
            // Octane 2.13 registra o callback task com a assinatura classica.
            // Com task coroutine ativo, Swoole 6 entrega Swoole\Server\Task
            // como segundo argumento e o servidor cai com TypeError.
            'task_enable_coroutine' => false,
            'enable_reuse_port' => true,
            'http_compression' => true,
            'compression_min_length' => 1024,
            'max_request' => (int) env('OCTANE_MAX_REQUESTS', 500),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            \App\Listeners\Octane\SelectiveDisconnectFromDatabases::class,
            CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    // ProcessoFilter NAO entra no warm: nao e singleton e o construtor captura
    // Request -- aquecer no boot resolveria um Request vazio de console. Os
    // services abaixo sao singletons sem estado de request (apenas cache de
    // enums imutaveis); nada user-scoped pode ser guardado em propriedades de
    // instancia deles.
    'warm' => [
        ...Octane::defaultServicesToWarm(),
        \App\Modules\Decretacoes\Services\ProcessoStatsService::class,
        \App\Modules\Decretacoes\Services\ProcessoQueryService::class,
    ],

    // O tenant da request agora vive no TenantContext (escopo de coroutine,
    // seguro sob Swoole), nao mais no container -- por isso 'tenant' saiu do
    // flush. EntradaProcessoService segue flushado por guardar estado de request.
    'flush' => [
        \App\Modules\Decretacoes\Services\EntradaProcessoService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | being handled by Octane. You may set this value to 0 to indicate that
    | there isn't a specific time limit on Octane request execution time.
    |
    */

    'max_execution_time' => 30,

];
