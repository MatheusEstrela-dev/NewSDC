<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Octane\Exceptions\TaskTimeoutException;
use Laravel\Octane\Facades\Octane;
use PDO;
use Swoole\Coroutine;

/**
 * Helper opt-in para hot paths: da uma conexao PDO por-coroutine a partir do
 * SwoolePdoPool sob Swoole, ou usa o PDO do Eloquent fora de coroutine.
 * NAO altera o resolver de conexao do Laravel (B2).
 */
final class Concurrency
{
    /**
     * Instante (monotonico) ate o qual paramos de despachar para os task
     * workers. Estado estatico sobrevive entre requests sob Octane, que e
     * exatamente a intencao: e um unico float por processo.
     */
    private static ?float $poolIndisponivelAte = null;

    /**
     * Roda a closure com um PDO emprestado do pool (Swoole) ou do Eloquent
     * (fallback). Retorna o que a closure retornar.
     *
     * @template T
     * @param  callable(PDO):T  $fn
     * @return T
     */
    public static function run(callable $fn): mixed
    {
        if (self::usaPool()) {
            /** @var \App\Support\Database\SwoolePdoPool $pool */
            $pool = app('swoole.pgsql.pool');

            return $pool->run($fn);
        }

        return $fn(DB::connection('pgsql')->getPdo());
    }

    /**
     * Roda N closures concorrentemente (cada uma com seu PDO) sob coroutine
     * Swoole; degrada para sequencial fora de coroutine. Resultados retornam
     * nas MESMAS chaves do array de entrada.
     *
     * @param  array<array-key, callable(PDO):mixed>  $closures
     * @return array<array-key, mixed>
     */
    public static function parallel(array $closures): array
    {
        if (! self::usaPool()) {
            $out = [];
            foreach ($closures as $chave => $fn) {
                $out[$chave] = self::run($fn);
            }

            return $out;
        }

        $wg = new \Swoole\Coroutine\WaitGroup();
        $out = [];

        foreach ($closures as $chave => $fn) {
            $wg->add();
            Coroutine::create(function () use ($chave, $fn, &$out, $wg): void {
                try {
                    $out[$chave] = self::run($fn);
                } finally {
                    $wg->done();
                }
            });
        }

        $wg->wait();

        return $out;
    }

    /**
     * Roda N closures SEM argumento em paralelo, escolhendo a estrategia mais
     * segura disponivel. Resultados retornam nas MESMAS chaves da entrada.
     *
     * Estrategias, na ordem:
     *  1. Task workers do Swoole (Octane::concurrently) - processos isolados,
     *     funciona com hooks off; timeout degrada para sequencial (nunca 500).
     *  2. Coroutines com WaitGroup - somente com hooks Swoole ligados (o
     *     CoroutineDatabaseManager da uma conexao por coroutine).
     *  3. Sequencial - RoadRunner local, artisan test, queue workers e
     *     qualquer chamada dentro de transacao aberta.
     *
     * Contrato das closures (estrategia 1 serializa para OUTRO processo):
     *  - usar static fn () => ... e NUNCA capturar $this, request, auth ou
     *    models; capture apenas escalares e arrays;
     *  - NUNCA definir a closure aninhada em outra closure na mesma expressao
     *    (ex.: array_map de fn que retorna fn): a serializacao extrai o fonte
     *    pela posicao no arquivo e closures na mesma linha serializam a
     *    closure errada; montar em foreach com static function () use (...);
     *  - resolver services dentro da closure: app(Service::class)->metodo();
     *  - retornos precisam ser serializaveis; `false` legitimo E permitido (o
     *    helper embrulha cada retorno, entao nao colide com o marcador de task
     *    nao concluida do Octane);
     *  - paginators: passar a pagina explicitamente e reaplicar withPath()
     *    no worker HTTP (task worker nao tem request).
     *
     * @param  array<array-key, \Closure(): mixed>  $closures
     * @param  int|null  $waitMs  timeout global em ms (default: octane.tasks.wait_ms)
     * @return array<array-key, mixed>
     */
    public static function tasks(array $closures, ?int $waitMs = null): array
    {
        if ($closures === []) {
            return [];
        }

        if (self::usaTaskWorkers() && ! self::poolIndisponivel()) {
            $waitMs ??= (int) config('octane.tasks.wait_ms', 5000);

            try {
                $out = Octane::concurrently(self::embrulhar($closures), $waitMs);
            } catch (TaskTimeoutException) {
                self::marcarPoolIndisponivel($waitMs, array_keys($closures));

                return self::sequencial($closures);
            }

            $resultados = [];
            foreach ($closures as $chave => $fn) {
                $valor = $out[$chave] ?? false;

                // Toda closure concluida volta embrulhada num array de um
                // elemento (ver embrulhar()). Logo, qualquer coisa que NAO seja
                // array e o `false` do Octane para "esta chave nao concluiu".
                if (is_array($valor) && array_key_exists(0, $valor)) {
                    $resultados[$chave] = $valor[0];

                    continue;
                }

                Log::warning('Concurrency::tasks: task nao concluida; recomputando chave.', [
                    'chave' => $chave,
                ]);

                $resultados[$chave] = $fn();
            }

            return $resultados;
        }

        if (self::usaCoroutinesComHooks()) {
            $wg = new \Swoole\Coroutine\WaitGroup();
            $out = [];
            $erro = null;

            foreach ($closures as $chave => $fn) {
                $wg->add();
                Coroutine::create(function () use ($chave, $fn, &$out, &$erro, $wg): void {
                    try {
                        $out[$chave] = $fn();
                    } catch (\Throwable $e) {
                        $erro ??= $e;
                    } finally {
                        $wg->done();
                    }
                });
            }

            $wg->wait();

            if ($erro !== null) {
                throw $erro;
            }

            return $out;
        }

        return self::sequencial($closures);
    }

    /**
     * Task workers reais do Swoole disponiveis neste processo?
     *
     * Exposto para hot paths CPU-bound (ex.: hash de senha) decidirem entre
     * offload para task worker e fallback sincrono sem cair na estrategia de
     * coroutines do Concurrency::tasks().
     */
    public static function taskWorkersAvailable(): bool
    {
        return config('octane.server') === 'swoole'
            && app()->bound(\Swoole\Http\Server::class)
            && (int) config('octane.swoole.options.task_worker_num', 0) > 0
            && DB::connection()->transactionLevel() === 0;
    }

    /**
     * Paralelismo de I/O no proprio HTTP worker disponivel?
     *
     * Use para hot paths de leitura: quando hooks Swoole + pool PDO estao
     * ativos, Concurrency::parallel() usa coroutines e uma conexao por closure.
     * Fora disso, prefira Concurrency::tasks() ou fallback sequencial.
     */
    public static function databaseParallelAvailable(): bool
    {
        return self::usaCoroutinesComHooks() && self::usaPool();
    }

    /**
     * Estrategia 1 disponivel? Exige o server Swoole DESTE processo (bound de
     * Swoole\Http\Server evita o SwooleHttpTaskDispatcher de loopback em
     * CLI/queue), task workers configurados e nenhuma transacao aberta (o
     * task worker tem conexao propria e nao enxerga a transacao local).
     */
    private static function usaTaskWorkers(): bool
    {
        return self::taskWorkersAvailable();
    }

    /**
     * Estrategia 2 disponivel? Somente dentro de coroutine com hooks Swoole
     * ligados (hook_flags != 0) e fora de transacao. Com hooks off, coroutines
     * nao dao yield em I/O e nao ha ganho - cai no sequencial.
     */
    private static function usaCoroutinesComHooks(): bool
    {
        return extension_loaded('swoole')
            && Coroutine::getCid() >= 0
            && (int) config('octane.swoole.options.hook_flags', 0) !== 0
            && DB::connection()->transactionLevel() === 0;
    }

    /**
     * Embrulha cada retorno num array de um elemento.
     *
     * O Octane devolve `false` na chave de uma task que nao concluiu no prazo
     * (ver SwooleTaskDispatcher::resolve). Sem embrulho, uma closure que
     * legitimamente retorna false era lida como "nao concluida" e recomputada no
     * worker HTTP -- trabalho duplicado silencioso, e a razao de o
     * PasswordVerifier precisar devolver 1/0 em vez de bool. Com o embrulho, o
     * marcador do Octane e o valor de negocio deixam de ocupar o mesmo espaco.
     *
     * As closures sao montadas em foreach com `static function () use (...)`, e
     * nao numa expressao aninhada: a SerializableClosure extrai o fonte pela
     * posicao no arquivo e closures irmas na mesma linha serializam a closure
     * errada.
     *
     * @param  array<array-key, \Closure(): mixed>  $closures
     * @return array<array-key, \Closure(): array{0: mixed}>
     */
    private static function embrulhar(array $closures): array
    {
        $embrulhadas = [];

        foreach ($closures as $chave => $fn) {
            $embrulhadas[$chave] = static function () use ($fn): array {
                return [$fn()];
            };
        }

        return $embrulhadas;
    }

    /**
     * Os task workers estao em periodo de carencia apos um timeout?
     *
     * Sem essa carencia, o timeout custava o PIOR dos dois mundos: o worker HTTP
     * ficava bloqueado wait_ms inteiros (5s por padrao) esperando o pool saturado
     * e SO ENTAO rodava todas as closures sequencialmente. Sob saturacao
     * sustentada cada request pagava espera + trabalho integral, o que realimenta
     * a propria saturacao em vez de alivia-la. Com a carencia, os requests
     * seguintes vao direto ao sequencial e pagam so o trabalho.
     */
    private static function poolIndisponivel(): bool
    {
        if (self::$poolIndisponivelAte === null) {
            return false;
        }

        if (microtime(true) < self::$poolIndisponivelAte) {
            return true;
        }

        self::$poolIndisponivelAte = null;

        return false;
    }

    /**
     * @param  list<array-key>  $chaves
     */
    private static function marcarPoolIndisponivel(int $waitMs, array $chaves): void
    {
        $carencia = (float) config('octane.tasks.cooldown_s', 10);

        self::$poolIndisponivelAte = $carencia > 0
            ? microtime(true) + $carencia
            : null;

        Log::warning('Concurrency::tasks: timeout nos task workers; sequencial e carencia no pool.', [
            'wait_ms' => $waitMs,
            'carencia_s' => $carencia,
            'chaves' => $chaves,
        ]);
    }

    /**
     * Zera a carencia. Existe para os testes do proprio mecanismo: o estado e
     * estatico e vazaria de um caso de teste para o seguinte.
     */
    public static function esquecerTimeouts(): void
    {
        self::$poolIndisponivelAte = null;
    }

    /**
     * Fallback: executa as closures em ordem, propagando excecoes.
     *
     * @param  array<array-key, \Closure(): mixed>  $closures
     * @return array<array-key, mixed>
     */
    private static function sequencial(array $closures): array
    {
        $out = [];
        foreach ($closures as $chave => $fn) {
            $out[$chave] = $fn();
        }

        return $out;
    }

    /**
     * Verdadeiro quando ha pool registrado E estamos dentro de uma coroutine
     * Swoole (getCid() >= 0). Fora disso, fallback sincrono.
     */
    private static function usaPool(): bool
    {
        return extension_loaded('swoole')
            && Coroutine::getCid() >= 0
            && app()->bound('swoole.pgsql.pool');
    }
}
