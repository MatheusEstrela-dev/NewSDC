<?php

declare(strict_types=1);

namespace App\Support;

use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

/**
 * Executa tarefas independentes em paralelo via coroutines do Swoole quando ha
 * contexto de coroutine; cai para execucao sequencial em qualquer outro lugar
 * (FrankenPHP, RoadRunner, CLI, testes). Resiliencia no codigo: o mesmo call
 * site funciona com ou sem Swoole.
 *
 * ATENCAO sobre banco: paralelizar tarefas que tocam o PDO so e seguro se cada
 * coroutine usar a sua propria conexao (ver App\Support\Database\SwoolePdoPool).
 * O Eloquent na conexao default compartilha um unico PDO por worker; sob
 * coroutines concorrentes isso corrompe o protocolo do Postgres. Use este helper
 * com tarefas CPU/cache/HTTP, ou com queries que peguem conexao do pool.
 */
final class Concurrency
{
    /**
     * @param  array<string,callable>  $tasks  mapa chave => closure
     * @return array<string,mixed>             resultados na mesma chave
     */
    public static function run(array $tasks): array
    {
        if (! self::inCoroutine()) {
            return array_map(static fn (callable $task) => $task(), $tasks);
        }

        $wg = new WaitGroup();
        $results = [];
        $errors = [];

        foreach ($tasks as $key => $task) {
            $wg->add();
            Coroutine::create(static function () use ($wg, $task, $key, &$results, &$errors): void {
                try {
                    $results[$key] = $task();
                } catch (\Throwable $e) {
                    // Sem capturar, a task falha em silencio e a chave some do
                    // resultado -> undefined index no chamador. Coletamos e
                    // relancamos a primeira apos o wait (fail-fast).
                    $errors[$key] = $e;
                } finally {
                    $wg->done();
                }
            });
        }

        $wg->wait();

        if ($errors !== []) {
            throw reset($errors);
        }

        return $results;
    }

    /**
     * Ha um scheduler de coroutine ativo? (extensao carregada e dentro de uma
     * coroutine — cid > 0). Fora disso, paralelizar nao e possivel nem seguro.
     */
    public static function inCoroutine(): bool
    {
        return extension_loaded('swoole')
            && class_exists(Coroutine::class)
            && Coroutine::getCid() > 0;
    }
}
