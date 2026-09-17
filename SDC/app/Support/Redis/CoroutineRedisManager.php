<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\RedisManager;

/**
 * RedisManager coroutine-aware.
 *
 * Dentro de uma coroutine Swoole (com hooks on), empresta uma conexao propria
 * do SwooleRedisPool por Coroutine::getCid(), isolando o socket entre coroutines
 * concorrentes (resolve "Socket already bound to another coroutine"). Fora de
 * coroutine, ou quando nao ha pool registrado para a conexao (hooks off),
 * delega ao RedisManager padrao do framework.
 *
 * Transparente para Cache/Session/spatie-permission: eles continuam pedindo a
 * conexao via o binding 'redis' do container, sem mudar codigo de aplicacao.
 */
final class CoroutineRedisManager extends RedisManager
{
    /** @var array<int,array<string,Connection>> cid => name => Connection */
    private array $coroutineConnections = [];

    /** @var array<string,SwooleRedisPool> name => pool */
    private array $pools = [];

    /** @var array<string,true> nomes cujo pool nao pode ser criado (nao retentar) */
    private array $poolsIrrecuperaveis = [];

    public function registerPool(string $name, SwooleRedisPool $pool): void
    {
        $this->pools[$name] = $pool;
    }

    public function poolDiagnostics(): array
    {
        $diagnostics = [];

        foreach ($this->pools as $name => $pool) {
            $diagnostics[$name] = [
                'capacity' => $pool->capacity(),
                'created' => $pool->created(),
                'available' => $pool->available(),
                'timeout_seconds' => $pool->timeout(),
            ];
        }

        ksort($diagnostics);

        return $diagnostics;
    }

    public function connection($name = null)
    {
        $name = $name ?: 'default';

        $cid = $this->coroutineId();

        if ($cid > 0) {
            $pool = $this->poolPara($name);

            if ($pool !== null) {
                if (! isset($this->coroutineConnections[$cid][$name])) {
                    $client = $pool->acquire();
                    $this->coroutineConnections[$cid][$name] = $this->wrap($name, $client);
                    // Garante devolucao tambem em coroutines filhas e excecoes,
                    // sem depender do evento de termino da requisicao HTTP.
                    \Swoole\Coroutine::defer(fn () => $this->releaseCoroutine($cid));
                }

                return $this->coroutineConnections[$cid][$name];
            }
        }

        return parent::connection($name);
    }

    /**
     * Pool da conexao, criado sob demanda se ainda nao existir.
     *
     * O registro normal vem do OctaneServiceProvider no WorkerStarting. Esta
     * criacao tardia existe porque a ALTERNATIVA e catastrofica: sem pool,
     * dentro de uma coroutine, caiamos no RedisManager padrao -- que cacheia
     * UMA Connection por nome e a entrega a todas as coroutines do worker.
     * Duas delas lendo o mesmo socket produz
     *
     *   Swoole\Error: Socket#N has already been bound to another coroutine
     *
     * que e um Error nao capturado, mata o worker e leva o Octane a servir
     * com menos processos ate parar de responder. Foi o que aconteceu: em
     * parte dos workers o registro no boot nao ocorreu e a sonda mostrou
     * 'pools=nenhum' durante requisicoes reais.
     *
     * Ou seja: o padrao anterior era compartilhar socket (quebra o servidor)
     * e o novo e abrir um pool (custa conexoes de Redis, que sao baratas).
     * Entre os dois, o unico aceitavel e o segundo. Se nem o pool puder ser
     * criado, ai sim delega ao manager padrao -- nesse ponto o Redis esta
     * fora do ar e a falha aparece como falha, nao como corrupcao silenciosa.
     */
    private function poolPara(string $name): ?SwooleRedisPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if (isset($this->poolsIrrecuperaveis[$name])) {
            return null;
        }

        try {
            $pool = SwooleRedisPool::fromConnection(
                $name,
                (int) env('OCTANE_REDIS_POOL_SIZE', 16),
                (float) env('OCTANE_REDIS_POOL_TIMEOUT', 3.0),
            );
        } catch (\Throwable $e) {
            // Nao insiste a cada chamada: sem isto, uma conexao mal
            // configurada tentaria abrir pool em todo comando de Redis.
            $this->poolsIrrecuperaveis[$name] = true;

            return null;
        }

        return $this->pools[$name] = $pool;
    }

    /**
     * Devolve ao pool todas as conexoes do cid. Chamar no RequestTerminated da
     * coroutine principal do request.
     */
    public function releaseCoroutine(int $cid): void
    {
        foreach ($this->coroutineConnections[$cid] ?? [] as $name => $conn) {
            if (isset($this->pools[$name])) {
                $this->pools[$name]->release($conn->client());
            }
        }
        unset($this->coroutineConnections[$cid]);
    }

    private function coroutineId(): int
    {
        if (! extension_loaded('swoole') || ! class_exists(\Swoole\Coroutine::class)) {
            return -1;
        }

        return \Swoole\Coroutine::getCid();
    }

    /** Embrulha um \Redis cru numa PhpRedisConnection do Laravel. */
    private function wrap(string $name, object $client): Connection
    {
        // PhpRedisConnection::__construct($client, ?callable $connector = null, array $config = [])
        return new PhpRedisConnection(
            $client,
            null,
            $this->config[$name] ?? []
        );
    }
}
