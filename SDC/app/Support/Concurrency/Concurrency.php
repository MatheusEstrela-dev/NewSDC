<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use Illuminate\Support\Facades\DB;
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
