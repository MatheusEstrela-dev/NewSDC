<?php

namespace App\Providers;

use App\Support\Database\SwoolePdoPool;
use App\Support\Redis\CoroutineRedisManager;
use App\Support\Redis\SwooleRedisPool;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\WorkerStarting;

class OctaneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->isRunningInOctane()) {
            return;
        }

        $this->app->singleton('octane.cache', function () {
            return collect();
        });

        // Com hooks Swoole ligados, troca o RedisManager por um coroutine-aware
        // (conexao por cid via pool). Com hooks off, mantem o RedisManager padrao
        // -> comportamento identico ao modo sincrono estavel.
        //
        // O RedisServiceProvider do framework e DEFERRED e bindaria 'redis' ao
        // resolver, sobrescrevendo um singleton nosso. Por isso usamos extend():
        // o extender roda depois que o 'redis' padrao e construido e o substitui,
        // preservando o singleton.
        if ($this->hooksEnabled()) {
            $this->app->extend('redis', function ($manager, $app) {
                $config = $app['config']['database.redis'] ?? [];

                return new CoroutineRedisManager(
                    $app,
                    $config['client'] ?? 'phpredis',
                    $config
                );
            });
            $this->forgetRedisBackedSingletons();

            // Sob hooks, troca o DatabaseManager por um coroutine-aware: a conexao
            // 'pgsql' passa a ser resolvida por-coroutine (PDO do SwoolePdoPool),
            // isolando socket e estado de transacao entre coroutines.
            $this->app->extend('db', function ($manager, $app) {
                return new \App\Support\Database\CoroutineDatabaseManager($app, $app['db.factory']);
            });
        }
    }

    public function boot(): void
    {
        if (!$this->isRunningInOctane()) {
            return;
        }

        $this->app['events']->listen(WorkerStarting::class, function () {
            if ($this->hooksEnabled()) {
                $this->forgetRedisBackedSingletons();
            }
            $this->tuneWorkerRuntime();
            $this->warmCaches();


            // Pools de corrotina NAO existem em processo de task. Ver
            // ehProcessoDeTask(): o task worker e justamente onde bloquear e
            // o comportamento desejado, e tentar criar pool la e fatal.
            if (! $this->ehProcessoDeTask()) {
                $this->semCederExecucao(function (): void {
                    $this->bootSwoolePdoPool();
                    $this->bootSwooleRedisPools();
                });
            }
        });

        $this->app['events']->listen(RequestReceived::class, function () {
            $this->resetRequestState();
        });

        $this->app['events']->listen(RequestTerminated::class, function () {
            $this->flushRequestState();
            $this->releaseRedisCoroutine();
            $this->releasePgsqlCoroutine();
        });
    }

    /**
     * Roda a closure com os hooks de corrotina DESLIGADOS, restaurando-os
     * depois. Serve para o unico trecho do boot que nao pode ser interrompido.
     *
     * O PROBLEMA: o pre-aquecimento dos pools abre dezenas de conexoes. Com os
     * hooks ligados, cada abertura CEDE execucao -- medido: com hook_flags=0 o
     * warm() e atomico, com SWOOLE_HOOK_ALL ele cede no meio. Isso quebra uma
     * premissa do Octane: o OnWorkerStart dele so atribui
     * $workerState->worker DEPOIS de montar a aplicacao, e o callback de
     * request faz $workerState->worker->handle(...) sem verificar nulo.
     *
     * Ou seja, enquanto o warm cede, o Swoole pode entregar uma requisicao a
     * um worker que ainda nao terminou de subir, e ela morre com
     * 'Call to a member function handle() on null'. Como o Octane trata isso
     * como falha de boot (bootWorker engole a excecao, deixa o worker nulo e
     * pede shutdown), o processo fica inutil para sempre -- e os requests
     * roteados para ele simplesmente penduram.
     *
     * Com os hooks desligados aqui, o warm volta a ser bloqueante e o boot
     * volta a ser atomico. O custo e alguns milissegundos por worker, uma vez
     * na vida dele; o beneficio e o worker so ficar alcancavel quando existir.
     */
    protected function semCederExecucao(callable $fn): void
    {
        if (! class_exists(\Swoole\Runtime::class)
            || ! method_exists(\Swoole\Runtime::class, 'setHookFlags')) {
            $fn();

            return;
        }

        $flags = \Swoole\Runtime::getHookFlags();
        \Swoole\Runtime::setHookFlags(0);

        try {
            $fn();
        } finally {
            // finally, e nao depois da chamada: uma falha ao aquecer nao pode
            // deixar o worker inteiro rodando sem hooks pelo resto da vida.
            \Swoole\Runtime::setHookFlags($flags);
        }
    }

    /**
     * Este worker e um processo de TASK (e nao um worker HTTP)?
     *
     * Importa porque os dois papeis querem coisas opostas. O worker HTTP, sob
     * hooks, precisa de uma conexao por coroutine para poder ceder execucao
     * enquanto espera o banco. O processo de task existe exatamente para
     * BLOQUEAR em paz, isolando trabalho pesado do pool HTTP -- ele quer PDO
     * comum, nao pool.
     *
     * E nao e so preferencia: com hook_flags != 0 e task_enable_coroutine
     * false, montar o pool no processo de task e FATAL. O warm() abre um
     * Coroutine\run() para preencher o channel e o Swoole recusa com
     * 'Unable to use async-io in task processes'. O processo morre, o manager
     * o recria, ele morre de novo -- um loop de respawn que nao aparece como
     * erro de request, so como task worker que nunca responde.
     *
     * A alternativa seria task_enable_coroutine=true, que esta desligado de
     * proposito: nesta versao do Octane o callback de task tem a assinatura
     * classica e o Swoole 6 passaria um Swoole\Server\Task como segundo
     * argumento, derrubando o servidor por TypeError (ver config/octane.php).
     */
    protected function ehProcessoDeTask(): bool
    {
        if (! $this->app->bound(\Swoole\Http\Server::class)) {
            return false;
        }

        try {
            return (bool) ($this->app->make(\Swoole\Http\Server::class)->taskworker ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function isRunningInOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE'])
            || env('OCTANE_SERVER') !== null
            || (method_exists($this->app, 'runningInOctane') && $this->app->runningInOctane());
    }

    /**
     * Tuning de runtime por worker (uma vez, no boot do worker):
     *
     * 1. GC manual: desliga o ciclo automatico do GC — em worker residente ele
     *    dispara no MEIO de requests (pausas de 15-50ms imprevisiveis). A
     *    coleta continua acontecendo no boundary entre requests pelo listener
     *    CollectGarbage do Octane (config octane.garbage, hoje 50MB), que chama
     *    gc_collect_cycles() manualmente — funciona mesmo com gc_disable().
     *
     * 2. CPU affinity: fixa o worker N no core N%vCores — sem isso o SO migra
     *    workers entre cores e invalida cache L1/L2 a cada troca. So faz
     *    sentido (e so existe) sob Swoole/Linux; falha e silenciosa por ser
     *    otimizacao.
     */
    protected function tuneWorkerRuntime(): void
    {
        gc_disable();

        // Swoole 6 removeu a funcao global swoole_set_cpu_affinity; o caminho
        // atual e Swoole\Process::setAffinity (mesmo sched_setaffinity).
        if (! function_exists('swoole_cpu_num')
            || ! class_exists(\Swoole\Process::class)
            || ! method_exists(\Swoole\Process::class, 'setAffinity')) {
            return;
        }

        try {
            if ($this->app->bound(\Swoole\Http\Server::class)) {
                $workerId = (int) ($this->app->make(\Swoole\Http\Server::class)->worker_id ?? -1);
                if ($workerId >= 0) {
                    \Swoole\Process::setAffinity([$workerId % max(1, swoole_cpu_num())]);
                }
            }
        } catch (\Throwable $e) {
            // Affinity e otimizacao opcional; nunca derruba o worker.
        }
    }

    /**
     * Sob Swoole, cria um pool de conexoes pgsql por worker (uma conexao por
     * coroutine). Inerte em FrankenPHP/RoadRunner: o codigo de aplicacao usa o
     * pool quando ligado (App\Support\Concurrency) ou cai no Eloquent normal.
     * Guardado para nao instanciar pool fora do Swoole.
     */
    protected function bootSwoolePdoPool(): void
    {
        if (! $this->isSwoole()) {
            return;
        }

        try {
            $size = (int) env('SWOOLE_PG_POOL_SIZE', 16);
            $timeout = (float) env('SWOOLE_PG_POOL_TIMEOUT', 3.0);

            $this->app->singleton('swoole.pgsql.pool', fn () => SwoolePdoPool::fromConnection('pgsql', $size, $timeout));
            if ($this->hooksEnabled()) {
                $this->app->make('swoole.pgsql.pool')->warm();
            }
        } catch (\Throwable $e) {
            // Pool e otimizacao opcional; nunca derruba o worker se falhar.
        }
    }

    /**
     * Cria os pools Redis (default db0, cache db1) por worker quando os hooks
     * Swoole estao ligados. Falha do pool nunca derruba o worker.
     */
    protected function bootSwooleRedisPools(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        try {
            $redis = $this->app->make('redis');
            if (! $redis instanceof CoroutineRedisManager) {
                return;
            }

            $size = (int) env('OCTANE_REDIS_POOL_SIZE', 16);
            $timeout = (float) env('OCTANE_REDIS_POOL_TIMEOUT', 3.0);

            foreach (['default', 'cache'] as $name) {
                $pool = SwooleRedisPool::fromConnection($name, $size, $timeout);
                $pool->warm(); // pre-aquece no boot: evita handshake TLS no burst -> sem timeout de acquire
                $redis->registerPool($name, $pool);
            }
        } catch (\Throwable $e) {
            // Pool e otimizacao opcional; nunca derruba o worker se falhar.
        }
    }

    /**
     * Devolve ao pool as conexoes Redis emprestadas pela coroutine do request.
     */
    protected function releaseRedisCoroutine(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        $cid = \Swoole\Coroutine::getCid();
        if ($cid <= 0) {
            return;
        }

        try {
            $redis = $this->app->make('redis');
            if ($redis instanceof CoroutineRedisManager) {
                $redis->releaseCoroutine($cid);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Devolve ao pool a conexao pgsql emprestada pela coroutine do request
     * (rollback de transacao aberta antes de devolver). Inerte fora de hooks.
     */
    protected function releasePgsqlCoroutine(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        try {
            $db = $this->app->make('db');
            if ($db instanceof \App\Support\Database\CoroutineDatabaseManager) {
                $db->releaseCurrentCoroutine();
            }
        } catch (\Throwable $e) {
        }
    }

    protected function isSwoole(): bool
    {
        return extension_loaded('swoole')
            && config('octane.server') === 'swoole';
    }

    /**
     * Os hooks Swoole estao ligados? (hook_flags != 0). So entao o pool Redis
     * por-coroutine entra em acao; com hooks off, RedisManager padrao.
     */
    protected function hooksEnabled(): bool
    {
        return $this->isSwoole()
            && (int) config('octane.swoole.options.hook_flags', 0) !== 0;
    }

    protected function forgetRedisBackedSingletons(): void
    {
        foreach (['redis', 'cache', 'cache.store', 'cache.psr6'] as $abstract) {
            if ($this->app->resolved($abstract)) {
                $this->app->forgetInstance($abstract);
            }
        }
    }

    protected function warmCaches(): void
    {
        try {
            if (class_exists(\App\Modules\Decretacoes\Services\ProcessoQueryService::class)) {
                $this->app->make(\App\Modules\Decretacoes\Services\ProcessoQueryService::class);
            }
        } catch (\Throwable $e) {
        }
    }

    protected function resetRequestState(): void
    {
        if ($this->app->bound('octane.cache')) {
            $this->app->make('octane.cache')->forget('request_stats');
        }
    }

    protected function flushRequestState(): void
    {
        if ($this->app->bound(\Illuminate\Contracts\Debug\ExceptionHandler::class)) {
            try {
                $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
                if (method_exists($handler, 'forgetExceptions')) {
                    $handler->forgetExceptions();
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
