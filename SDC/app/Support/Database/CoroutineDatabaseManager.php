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

    public function connection($name = null)
    {
        if ($this->shouldPool($name)) {
            $ctx = Coroutine::getContext();
            if (! isset($ctx[self::CTX_KEY])) {
                $ctx[self::CTX_KEY] = $this->makePooledPgsqlConnection();
            }

            return $ctx[self::CTX_KEY];
        }

        return parent::connection($name);
    }

    /** Devolve ao pool a conexao da coroutine atual (chamar no RequestTerminated). */
    public function releaseCurrentCoroutine(): void
    {
        if (! $this->inCoroutine()) {
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

        $pool = $this->app->make('swoole.pgsql.pool');
        try {
            $pool->release($conn->getPdo());
        } catch (\Throwable $e) {
            $pool->discard();
        }

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
        $config = $this->app['config']['database.connections.pgsql'];

        // Instancia a PostgresConnection com o PDO emprestado (o construtor ja
        // aplica grammar/processor pgsql). configure() (do DatabaseManager pai)
        // injeta event dispatcher (DB::listen/circuit breaker), transaction
        // manager e reconnector — ConnectionFactory::createConnection e protected.
        $connection = new \Illuminate\Database\PostgresConnection(
            $pdo,
            $config['database'] ?? '',
            $config['prefix'] ?? '',
            $config
        );

        return $this->configure($connection, 'write');
    }
}
