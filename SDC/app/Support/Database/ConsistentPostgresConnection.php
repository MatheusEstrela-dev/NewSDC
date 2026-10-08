<?php

declare(strict_types=1);

namespace App\Support\Database;

use App\Exceptions\PoolEsgotado;
use Closure;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Distribui leituras apenas para standbys que alcancaram o WAL do primario.
 * O marco e renovado em cada operacao Octane/job; sticky protege pos-escrita.
 */
final class ConsistentPostgresConnection extends PostgresConnection
{
    private array $replicaPdos = [];

    private ?Closure $replicaResolver = null;

    private ?Closure $replicaDiscarder = null;

    private ?string $selectedReplica = null;

    private ?int $nextReplica = null;

    private bool $replicaChecked = false;

    private bool $primaryOnly = false;

    private ?array $readFence = null;

    private ?int $operationTimeout = null;

    private array $timeoutPdos = [];

    public function startReplicaAt(int $offset): self
    {
        $this->nextReplica = $offset;

        return $this;
    }

    public function setReplicaPdoResolver(Closure $resolver, Closure $discarder): self
    {
        $this->replicaResolver = $resolver;
        $this->replicaDiscarder = $discarder;

        return $this;
    }

    public function getReadPdo()
    {
        $this->reconnectIfMissingConnection();
        if ($this->transactions > 0 || $this->readOnWriteConnection
            || ($this->recordsModified && $this->getConfig('sticky')) || $this->primaryOnly) {
            return $this->getPdo();
        }

        if ($this->replicaChecked) {
            if (! $this->configureReaderTimeout($this->readPdo, $this->selectedReplica)) {
                return $this->getPdo();
            }
            $this->latestPdoTypeRetrieved = 'read';

            return $this->readPdo;
        }

        if (! $this->ensureReadFence()) {
            return $this->getPdo();
        }
        $fence = $this->readFence;
        $hosts = array_values((array) ($this->readPdoConfig['host'] ?? []));
        if ($hosts === []) {
            $this->primaryOnly = true;

            return $this->getPdo();
        }

        $this->nextReplica ??= random_int(0, count($hosts) - 1);
        $start = $this->nextReplica++ % count($hosts);
        foreach (range(0, count($hosts) - 1) as $offset) {
            $host = $hosts[($start + $offset) % count($hosts)];
            try {
                $pdo = $this->replicaResolver
                    ? ($this->replicaResolver)($host)
                    : ($this->replicaPdos[$host] ??= (new ReadonlyPostgresConnector)->connect(
                        array_replace($this->readPdoConfig, ['host' => $host])
                    ));
                $this->applyOperationTimeout($pdo, $host);
                $check = $pdo->prepare(
                    'SELECT pg_is_in_recovery() AND pg_last_wal_replay_lsn() >= CAST(? AS pg_lsn) '
                    .'AND system_identifier::text = ? AND timeline_id = ? '
                    .'AND current_setting(\'transaction_read_only\')::boolean '
                    .'FROM pg_control_system(), pg_control_checkpoint()'
                );
                $check->execute([$fence['lsn'], $fence['system_id'], $fence['timeline_id']]);
                $ready = $check->fetchColumn();
                if ($ready === true || $ready === 't' || $ready === 1) {
                    $this->selectedReplica = $host;
                    $this->readPdo = $pdo;
                    $this->replicaChecked = true;
                    $this->latestPdoTypeRetrieved = 'read';

                    return $pdo;
                }
            } catch (PDOException|PoolEsgotado) {
                $this->discardReplica($host);
            }
        }

        $this->primaryOnly = true;

        return $this->getPdo();
    }

    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            return parent::runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $exception) {
            $cause = $exception->getPrevious();
            $state = $cause instanceof PDOException ? ($cause->errorInfo[0] ?? $cause->getCode()) : null;
            // Somente a consulta enviada ao leitor pode ser repetida no primario.
            // Escritas nunca sao repetidas: perda da resposta ao COMMIT e ambigua.
            if ($this->latestPdoTypeRetrieved !== 'read' || $this->transactions > 0
                || ! (str_starts_with((string) $state, '08')
                    || ($cause !== null && $this->causedByLostConnection($cause))
                    || in_array($state, ['25006', '40001', '57P01', '57P02', '57P03'], true))) {
                throw $exception;
            }

            if ($this->selectedReplica !== null) {
                $this->discardReplica($this->selectedReplica);
            }
            $this->primaryOnly = true;

            return parent::runQueryCallback($query, $bindings, $callback);
        }
    }

    protected function handleQueryException(QueryException $exception, $query, $bindings, Closure $callback)
    {
        // O framework repetiria ate INSERT apos reconectar. Uma resposta perdida
        // nao informa se a escrita foi confirmada; limpar permite reconectar na
        // proxima operacao, mas nunca repetir automaticamente esta operacao.
        $cause = $exception->getPrevious();
        if ($this->transactions === 0 && $cause !== null && $this->causedByLostConnection($cause)) {
            $this->disconnect();
        }

        throw $exception;
    }

    public function forgetRecordModificationState()
    {
        $this->setOperationTimeout(null);
        parent::forgetRecordModificationState();
        $this->replicaChecked = false;
        $this->primaryOnly = false;
        $this->selectedReplica = null;
        $this->readPdo = null;
        $this->readFence = null;
    }

    public function setReadPdo($pdo)
    {
        $this->replicaChecked = false;
        $this->primaryOnly = false;
        $this->readFence = null;

        return parent::setReadPdo($pdo);
    }

    public function disconnect()
    {
        $this->replicaPdos = [];
        $this->selectedReplica = null;
        $this->readFence = null;
        parent::disconnect();
    }

    private function discardReplica(string $host): void
    {
        unset($this->replicaPdos[$host]);
        foreach ($this->timeoutPdos as $id => [, $timeoutHost]) {
            if ($timeoutHost === $host) {
                unset($this->timeoutPdos[$id]);
            }
        }
        if ($this->replicaDiscarder !== null) {
            ($this->replicaDiscarder)($host);
        }
    }

    public function getPdo()
    {
        $pdo = parent::getPdo();
        if ($pdo instanceof PDO) {
            $this->applyOperationTimeout($pdo);
        }

        return $pdo;
    }

    public function setOperationTimeout(?int $milliseconds): void
    {
        if ($milliseconds !== null) {
            if ($milliseconds < 0 || $milliseconds > 2147483647) {
                throw new InvalidArgumentException('Timeout PostgreSQL fora do intervalo permitido.');
            }
            $this->operationTimeout = $milliseconds;
            $this->reconnectIfMissingConnection();
            try {
                $this->getPdo();
            } catch (PDOException $exception) {
                $this->disconnect();

                throw $exception;
            }

            $readers = $this->timeoutPdos;
            if ($this->replicaChecked && $this->readPdo instanceof PDO) {
                $readers[spl_object_id($this->readPdo)] = [$this->readPdo, $this->selectedReplica];
            }
            foreach ($readers as [$pdo, $host]) {
                if ($host !== null) {
                    $this->configureReaderTimeout($pdo, $host);
                }
            }

            return;
        }

        $this->operationTimeout = null;
        foreach ($this->timeoutPdos as [$pdo, $host]) {
            try {
                $pdo->exec('SET statement_timeout = DEFAULT; SET idle_in_transaction_session_timeout = DEFAULT');
            } catch (PDOException) {
                if ($host !== null) {
                    $this->discardReplica($host);
                } elseif ($this->getRawPdo() === $pdo) {
                    $this->disconnect();
                }
            }
        }
        $this->timeoutPdos = [];
    }

    public function getOperationTimeout(): ?int
    {
        return $this->operationTimeout;
    }

    private function applyOperationTimeout(PDO $pdo, ?string $host = null): void
    {
        if ($this->operationTimeout === null
            || ($this->timeoutPdos[spl_object_id($pdo)][2] ?? null) === $this->operationTimeout) {
            return;
        }

        $this->timeoutPdos[spl_object_id($pdo)] = [$pdo, $host, $this->operationTimeout];
        $pdo->exec("SET statement_timeout = {$this->operationTimeout}; SET idle_in_transaction_session_timeout = 60000");
    }

    private function configureReaderTimeout(PDO $pdo, string $host): bool
    {
        try {
            $this->applyOperationTimeout($pdo, $host);

            return true;
        } catch (PDOException) {
            $this->discardReplica($host);
            if ($this->selectedReplica === $host) {
                $this->primaryOnly = true;
            }

            return false;
        }
    }

    private function ensureReadFence(): bool
    {
        if ($this->readFence !== null) {
            return true;
        }

        $retry = $this->transactions === 0;
        // Somente este SELECT pode ser repetido ao recuperar uma sessao ociosa.
        // Sem marco do primario, nenhuma copia e servida.
        while (true) {
            try {
                $this->reconnectIfMissingConnection();
                $this->readFence = $this->getPdo()->query(
                    'SELECT pg_current_wal_lsn() AS lsn, system_identifier::text AS system_id, timeline_id '
                    .'FROM pg_control_system(), pg_control_checkpoint()'
                )->fetch(PDO::FETCH_ASSOC);

                return true;
            } catch (PDOException $exception) {
                if (($exception->errorInfo[0] ?? null) === '42501') {
                    $this->primaryOnly = true;

                    return false;
                }
                if ($this->causedByLostConnection($exception)) {
                    $this->disconnect();
                    if ($retry) {
                        $retry = false;

                        continue;
                    }
                }
                throw $exception;
            }
        }
    }
}
