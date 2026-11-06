<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Illuminate\Redis\Connections\Connection;
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

            if ($pool === null) {
                throw new \RuntimeException("Pool Redis '{$name}' indisponivel para coroutine.");
            }

            if (! isset($this->coroutineConnections[$cid][$name])) {
                $client = $pool->acquire();
                try {
                    $connection = $this->wrap($name, $client);
                    // Garante devolucao tambem em coroutines filhas e excecoes,
                    // sem depender do evento de termino da requisicao HTTP.
                    \Swoole\Coroutine::defer(fn () => $this->releaseCoroutine($cid));
                    $this->coroutineConnections[$cid][$name] = $connection;
                } catch (\Throwable $e) {
                    try {
                        $pool->release($client);
                    } catch (\Throwable) {
                        $pool->discard();
                    }

                    throw $e;
                }
            }

            return $this->coroutineConnections[$cid][$name];
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
     * Quando nao e possivel criar o pool, connection() falha explicitamente:
     * usar o RedisManager compartilhado nessa situacao voltaria a expor o
     * mesmo socket a coroutines diferentes.
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
        $connections = $this->coroutineConnections[$cid] ?? [];
        unset($this->coroutineConnections[$cid]);

        foreach ($connections as $name => $conn) {
            if (isset($this->pools[$name])) {
                $pool = $this->pools[$name];
                if ($conn instanceof PooledPhpRedisConnection && $conn->shouldDiscard()) {
                    try {
                        $conn->disconnect();
                    } catch (\Throwable) {
                    }
                    $pool->discard();

                    continue;
                }

                try {
                    $pool->release($conn->client());
                } catch (\Throwable) {
                    $pool->discard();
                }
            }
        }
    }

    private function coroutineId(): int
    {
        if (! extension_loaded('swoole') || ! class_exists(\Swoole\Coroutine::class)) {
            return -1;
        }

        return \Swoole\Coroutine::getCid();
    }

    /** Embrulha um \Redis cru numa conexao que sinaliza falhas ao pool. */
    private function wrap(string $name, object $client): Connection
    {
        return new PooledPhpRedisConnection(
            $client,
            null,
            $this->config[$name] ?? []
        );
    }
}
