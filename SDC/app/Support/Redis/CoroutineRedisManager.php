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

    public function registerPool(string $name, SwooleRedisPool $pool): void
    {
        $this->pools[$name] = $pool;
    }

    public function connection($name = null)
    {
        $name = $name ?: 'default';

        $cid = $this->coroutineId();
        if ($cid > 0 && isset($this->pools[$name])) {
            if (! isset($this->coroutineConnections[$cid][$name])) {
                $client = $this->pools[$name]->acquire();
                $this->coroutineConnections[$cid][$name] = $this->wrap($name, $client);
            }

            return $this->coroutineConnections[$cid][$name];
        }

        return parent::connection($name);
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
