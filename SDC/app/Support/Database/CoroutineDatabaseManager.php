<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Swoole\Coroutine;

/**
 * DatabaseManager coroutine-aware. Sob hooks Swoole + dentro de coroutine,
 * a conexao 'pgsql' e resolvida POR-COROUTINE: um objeto Connection proprio
 * (PDO emprestado do SwoolePdoPool, embrulhado pela ConnectionFactory do
 * framework), guardado em Coroutine::getContext(). Isso isola tanto o socket
 * PDO quanto o estado de transacao (Connection::$transactions) entre coroutines.
 * Fora de coroutine / hooks off / outra conexao -> DatabaseManager padrao.
 */
final class CoroutineDatabaseManager extends DatabaseManager
{
    private const CTX_KEY = '__sdc_pgsql_coroutine_connection';

    private const READERS_KEY = '__sdc_pgsql_coroutine_readers';

    private const WRITER_KEY = '__sdc_pgsql_coroutine_writer';

    /**
     * Pool guardado na PRIMEIRA aquisicao, e nao resolvido na devolucao.
     *
     * A devolucao roda dentro de um Coroutine::defer, que dispara quando a
     * coroutine termina -- depois de o Octane ja ter desmontado o container
     * daquela requisicao. Resolver 'swoole.pgsql.pool' ali produzia
     * 'ReflectionException: Class "swoole.pgsql.pool" does not exist', e como
     * o proprio relatorio desse erro tambem precisa do container ('config'),
     * o segundo erro matava o processo. Guardar a referencia torna a
     * devolucao independente do ciclo de vida do container.
     */
    private ?SwoolePdoPool $pool = null;

    private array $readPools = [];

    private ?int $nextReplica = null;

    public function connection($name = null)
    {
        if ($this->shouldPool($name)) {
            $ctx = Coroutine::getContext();
            if (! isset($ctx[self::CTX_KEY])) {
                // Resolve o pool AQUI, onde o container existe.
                $this->pool ??= $this->app->make('swoole.pgsql.pool');
                $ctx[self::CTX_KEY] = $this->makePooledPgsqlConnection();
                // Filhas criadas pelos helpers nao disparam RequestTerminated.
                // A devolucao explicita no HTTP continua valida e e idempotente.
                Coroutine::defer(fn () => $this->releaseCurrentCoroutine());
            }

            return $ctx[self::CTX_KEY];
        }

        return parent::connection($name);
    }

    /**
     * Devolve ao pool a conexao da coroutine atual (chamar no RequestTerminated).
     *
     * Tudo aqui e defensivo de proposito: este metodo tambem roda por
     * Coroutine::defer, e uma excecao escapando de um defer nao tem quem a
     * pegue -- vira erro fatal e derruba o worker inteiro.
     */
    public function releaseCurrentCoroutine(): void
    {
        if (! $this->inCoroutine() || $this->pool === null) {
            return;
        }

        $ctx = Coroutine::getContext();
        $conn = $ctx[self::CTX_KEY] ?? null;
        if (! $conn instanceof Connection) {
            return;
        }

        // Transacao aberta no fim do request -> rollback antes de devolver,
        // senao a proxima coroutine que pegar o PDO herda a transacao.
        try {
            while ($conn->transactionLevel() > 0) {
                $conn->rollBack();
            }
        } catch (\Throwable $e) {
        }

        $writer = $ctx[self::WRITER_KEY] ?? null;
        if ($writer instanceof \PDO) {
            try {
                if ($conn->getRawPdo() === $writer) {
                    $this->pool->release($writer);
                } else {
                    $this->pool->discard();
                }
            } catch (\Throwable) {
                $this->pool->discard();
            }
        }
        unset($ctx[self::WRITER_KEY]);

        foreach ($ctx[self::READERS_KEY] ?? [] as [$readPool, $readPdo]) {
            try {
                $readPool->release($readPdo);
            } catch (\Throwable) {
                $readPool->discard();
            }
        }
        unset($ctx[self::READERS_KEY]);

        unset($ctx[self::CTX_KEY]);
    }

    private function shouldPool($name): bool
    {
        $resolved = $name ?: $this->getDefaultConnection();

        return $resolved === 'pgsql'
            && $this->inCoroutine()
            && $this->app->bound('swoole.pgsql.pool');
    }

    private function inCoroutine(): bool
    {
        return extension_loaded('swoole')
            && class_exists(Coroutine::class)
            && Coroutine::getCid() > 0;
    }

    private function makePooledPgsqlConnection(): Connection
    {
        $pool = $this->app->make('swoole.pgsql.pool');
        $pdo = $pool->acquire();
        $ctx = Coroutine::getContext();
        $ctx[self::WRITER_KEY] = $pdo;
        $config = $this->app['config']['database.connections.pgsql'];

        // Instancia a PostgresConnection com o PDO emprestado (o construtor ja
        // aplica grammar/processor pgsql). configure() (do DatabaseManager pai)
        // injeta event dispatcher (DB::listen/circuit breaker), transaction
        // manager e reconnector — ConnectionFactory::createConnection e protected.
        $class = ($config['replica_routing'] ?? false)
            ? ConsistentPostgresConnection::class : \Illuminate\Database\PostgresConnection::class;
        $connection = new $class(
            $pdo,
            $config['database'] ?? '',
            $config['prefix'] ?? '',
            $config
        );

        if ($connection instanceof ConsistentPostgresConnection) {
            $this->readPools = $this->app->make('swoole.pgsql.read.pools');
            $pools = $this->readPools;
            $ctx = Coroutine::getContext();
            $this->nextReplica ??= random_int(0, count($pools) - 1);
            $connection->startReplicaAt($this->nextReplica++);
            $connection->setReadPdoConfig(array_replace($config, $config['read']));
            $connection->setReplicaPdoResolver(
                static function (string $host) use ($pools, $ctx): \PDO {
                    $readers = $ctx[self::READERS_KEY] ?? [];
                    if (! isset($readers[$host])) {
                        $readers[$host] = [$pools[$host], $pools[$host]->acquire()];
                        $ctx[self::READERS_KEY] = $readers;
                    }

                    return $readers[$host][1];
                },
                static function (string $host) use ($ctx): void {
                    $readers = $ctx[self::READERS_KEY] ?? [];
                    if (isset($readers[$host])) {
                        $readers[$host][0]->discard();
                        unset($readers[$host]);
                        $ctx[self::READERS_KEY] = $readers;
                    }
                },
            );
        }

        $this->configure($connection, null);
        $connection->setReconnector(static function (Connection $connection) use ($pool, $ctx): void {
            if (isset($ctx[self::WRITER_KEY])) {
                $pool->discard();
                unset($ctx[self::WRITER_KEY]);
            }
            $pdo = $pool->acquire();
            $ctx[self::WRITER_KEY] = $pdo;
            $connection->setPdo($pdo);
            $connection->setReadPdo(null);
        });

        return $connection;
    }
}
