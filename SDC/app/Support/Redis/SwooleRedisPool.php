<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Redis;
use RuntimeException;
use Swoole\Coroutine\Channel;

/**
 * Pool de conexoes phpredis por worker para uso sob coroutines do Swoole.
 *
 * Espelha App\Support\Database\SwoolePdoPool: sob SWOOLE_HOOK_ALL varias
 * coroutines de um worker rodam I/O "ao mesmo tempo". Uma conexao Redis
 * compartilhada entre coroutines corrompe o socket ("Socket already bound to
 * another coroutine"). Este pool da a cada coroutine uma conexao propria,
 * emprestada/devolvida via Channel (bloqueio cooperativo, coroutine-safe).
 *
 * Uma instancia por conexao logica (default db0, cache db1): o construtor
 * recebe a fabrica que ja seleciona o database correto.
 */
final class SwooleRedisPool
{
    private Channel $channel;

    private int $created = 0;

    /** @param callable():object $factory cria uma conexao (\Redis em producao) */
    private function __construct(
        private $factory,
        private readonly int $size,
        private readonly float $timeout,
    ) {
        $this->channel = new Channel($size);
    }

    /**
     * Constroi o pool a partir de uma conexao redis do config/database.php.
     * Monta a conexao phpredis manualmente com TLS (Azure 6380) + auth + select.
     */
    public static function fromConnection(string $connection = 'default', int $size = 16, float $timeout = 3.0): self
    {
        $cfg = config("database.redis.{$connection}");
        if (! is_array($cfg)) {
            throw new RuntimeException("SwooleRedisPool: conexao redis '{$connection}' invalida.");
        }

        $scheme = $cfg['scheme'] ?? 'tcp';
        $host = ($scheme === 'tls' ? 'tls://' : '').($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 6379);
        $password = $cfg['password'] ?? null;
        $username = $cfg['username'] ?? null;
        $db = (int) ($cfg['database'] ?? 0);

        $factory = static function () use ($host, $port, $username, $password, $db): Redis {
            $r = new Redis();
            $r->connect($host, $port, 2.0);
            if ($password) {
                // Azure usa ACL user opcional; phpredis aceita [user, pass] ou pass.
                $username ? $r->auth([$username, $password]) : $r->auth($password);
            }
            $r->select($db);

            return $r;
        };

        return new self($factory, max(1, $size), $timeout);
    }

    /** Apenas testes: injeta a fabrica (sem Redis real). */
    public static function forTesting(int $size, float $timeout, callable $factory): self
    {
        return new self($factory, max(1, $size), $timeout);
    }

    public function available(): int
    {
        return $this->channel->length();
    }

    /**
     * Pre-cria as conexoes ate o teto (chamar no WorkerStarting). Move o custo
     * de handshake TLS do burst do request para o boot do worker, evitando
     * timeout de acquire no pico inicial. Falha de criacao interrompe o warm
     * sem derrubar (as restantes sobem on-demand).
     */
    public function warm(): void
    {
        while ($this->created < $this->size) {
            try {
                $conn = ($this->factory)();
            } catch (\Throwable $e) {
                break;
            }
            $this->created++;
            $this->channel->push($conn);
        }
    }

    /**
     * Empresta uma conexao, executa a closure e devolve ao final (mesmo em
     * excecao). Conexao morta (\RedisException) e descartada, nao devolvida.
     */
    public function run(callable $fn): mixed
    {
        $conn = $this->acquire();

        try {
            $result = $fn($conn);
        } catch (\RedisException $e) {
            $this->discard();
            throw $e;
        } catch (\Throwable $e) {
            $this->release($conn);
            throw $e;
        }

        $this->release($conn);

        return $result;
    }

    public function acquire(): object
    {
        // Cria sob demanda ate o teto; senao espera uma devolucao (com timeout).
        // Checagem + incremento sao contiguos (sem yield), seguros no scheduler.
        if ($this->created < $this->size && $this->channel->isEmpty()) {
            $this->created++;
            try {
                return ($this->factory)();
            } catch (\Throwable $e) {
                $this->created--;
                throw $e;
            }
        }

        $conn = $this->channel->pop($this->timeout);
        if ($conn === false) {
            throw new RuntimeException('SwooleRedisPool esgotado (timeout no acquire).');
        }

        return $conn;
    }

    public function release(object $conn): void
    {
        $this->channel->push($conn);
    }

    public function discard(): void
    {
        // Conexao descartada (nao volta ao Channel): libera um slot p/ recriar.
        $this->created = max(0, $this->created - 1);
    }
}
