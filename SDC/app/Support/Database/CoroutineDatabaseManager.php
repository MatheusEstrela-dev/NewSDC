<?php

declare(strict_types=1);

namespace App\Support\Database;

use ArrayAccess;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\PostgresConnection;

/**
 * DatabaseManager coroutine-aware. Sob hooks Swoole + dentro de coroutine,
 * cada conexao listada em database.coroutine_pools (PdoPoolRegistry) e
 * resolvida POR-COROUTINE: um objeto Connection proprio (PDO emprestado do
 * pool DAQUELA conexao, embrulhado pela ConnectionFactory do framework),
 * guardado no contexto da coroutine. Isso isola tanto o socket PDO quanto o
 * estado de transacao (Connection::$transactions) entre coroutines, e um pool
 * nunca empresta PDO para outra conexao: esgotar o pgsql_log nao toca o pgsql.
 * Fora de coroutine / hooks off / conexao nao listada -> DatabaseManager padrao.
 */
final class CoroutineDatabaseManager extends DatabaseManager
{
    private const CHAVE_CONEXAO = '__sdc_pdo_conexao:';

    private const CHAVE_ESCRITOR = '__sdc_pdo_escritor:';

    private const CHAVE_LEITORES = '__sdc_pdo_leitores:';

    private const CHAVE_DEVOLUCAO_AGENDADA = '__sdc_pdo_devolucao_agendada';

    /**
     * Pools guardados na PRIMEIRA aquisicao de cada conexao, e nao resolvidos
     * na devolucao.
     *
     * A devolucao roda dentro de um Coroutine::defer, que dispara quando a
     * coroutine termina -- depois de o Octane ja ter desmontado o container
     * daquela requisicao. Resolver o pool pelo container ali produzia
     * 'ReflectionException: Class "swoole.pgsql.pool" does not exist', e como
     * o proprio relatorio desse erro tambem precisa do container ('config'),
     * o segundo erro matava o processo. Guardar a referencia torna a
     * devolucao independente do ciclo de vida do container.
     *
     * @var array<string, PdoPool>
     */
    private array $pools = [];

    /** @var array<string, int> */
    private array $proximaReplica = [];

    /** @var array<string, HostsPrimariosConhecidos> */
    private array $primarios = [];

    private ?PdoPoolRegistry $registry = null;

    private readonly CoroutineContext $contexto;

    public function __construct($app, ConnectionFactory $factory, ?CoroutineContext $contexto = null)
    {
        parent::__construct($app, $factory);
        $this->contexto = $contexto ?? new SwooleCoroutineContext;
    }

    public function connection($name = null)
    {
        $nome = (string) ($name ?: $this->getDefaultConnection());

        if ($this->devePoolar($nome)) {
            $ctx = $this->contexto->storage();
            $chave = self::CHAVE_CONEXAO.$nome;
            if (! isset($ctx[$chave])) {
                // Filhas criadas pelos helpers nao disparam RequestTerminated.
                // A devolucao explicita no HTTP continua valida e e idempotente.
                // Agendar antes do acquire evita deixar um PDO sem limpeza se
                // o agendamento falhar. Um defer devolve todos os pools.
                if (! isset($ctx[self::CHAVE_DEVOLUCAO_AGENDADA])) {
                    $this->contexto->defer(fn () => $this->releaseCurrentCoroutine());
                    $ctx[self::CHAVE_DEVOLUCAO_AGENDADA] = true;
                }
                $ctx[$chave] = $this->criarConexaoPoolada($nome, $ctx);
            }

            return $ctx[$chave];
        }

        return parent::connection($name);
    }

    /**
     * Devolve aos pools as conexoes da coroutine atual (chamar no
     * RequestTerminated).
     *
     * Tudo aqui e defensivo de proposito: este metodo tambem roda por
     * Coroutine::defer, e uma excecao escapando de um defer nao tem quem a
     * pegue -- vira erro fatal e derruba o worker inteiro.
     */
    public function releaseCurrentCoroutine(): void
    {
        if ($this->pools === [] || ! $this->contexto->active()) {
            return;
        }

        $ctx = $this->contexto->storage();
        foreach ($this->pools as $nome => $pool) {
            try {
                $this->devolver($nome, $pool, $ctx);
            } catch (\Throwable) {
            }
        }
    }

    private function devolver(string $nome, PdoPool $pool, ArrayAccess $ctx): void
    {
        $conn = $ctx[self::CHAVE_CONEXAO.$nome] ?? null;
        $rollbackFailed = false;

        // Transacao aberta no fim do request -> rollback antes de devolver,
        // senao a proxima coroutine que pegar o PDO herda a transacao.
        if ($conn instanceof Connection) {
            try {
                while ($conn->transactionLevel() > 0) {
                    $conn->rollBack();
                }
            } catch (\Throwable) {
                $rollbackFailed = true;
            }
        }

        $writer = $ctx[self::CHAVE_ESCRITOR.$nome] ?? null;
        if ($writer instanceof \PDO) {
            try {
                if ($rollbackFailed || ! $conn instanceof Connection) {
                    $pool->discard();
                } elseif ($conn->getRawPdo() === $writer) {
                    $pool->release($writer);
                } else {
                    $pool->discard();
                }
            } catch (\Throwable) {
                $pool->discard();
            }
        }
        unset($ctx[self::CHAVE_ESCRITOR.$nome]);

        foreach ($ctx[self::CHAVE_LEITORES.$nome] ?? [] as [$readPool, $readPdo]) {
            try {
                $readPool->release($readPdo);
            } catch (\Throwable) {
                $readPool->discard();
            }
        }
        unset($ctx[self::CHAVE_LEITORES.$nome]);

        unset($ctx[self::CHAVE_CONEXAO.$nome]);
    }

    private function devePoolar(string $nome): bool
    {
        return $this->contexto->active()
            && ($this->registry()?->gerencia($nome) ?? false);
    }

    /**
     * O registry so existe depois do WorkerStarting (OctaneServiceProvider).
     * Antes disso, ou fora do Swoole, tudo cai no DatabaseManager padrao.
     */
    private function registry(): ?PdoPoolRegistry
    {
        if ($this->registry === null && $this->app->bound(PdoPoolRegistry::class)) {
            $this->registry = $this->app->make(PdoPoolRegistry::class);
        }

        return $this->registry;
    }

    private function criarConexaoPoolada(string $nome, ArrayAccess $ctx): Connection
    {
        // Resolve o pool AQUI, onde o container existe.
        $pool = $this->pools[$nome] ??= $this->registry()->pool($nome);
        $chaveEscritor = self::CHAVE_ESCRITOR.$nome;
        $pdo = $pool->acquire();
        $ctx[$chaveEscritor] = $pdo;

        try {
            return $this->montarConexao($nome, $pool, $pdo, $ctx);
        } catch (\Throwable $e) {
            // Sem Connection no contexto ninguem devolveria este PDO: o slot
            // vazaria e o pool encolheria a cada falha ate travar.
            unset($ctx[$chaveEscritor]);
            $pool->discard();
            throw $e;
        }
    }

    private function montarConexao(string $nome, PdoPool $pool, \PDO $pdo, ArrayAccess $ctx): Connection
    {
        // 'name' explicito: sem ele getName() seria null e, com mais de um
        // pool, os afterCommit do db.transactions de conexoes diferentes se
        // misturariam (o manager indexa as transacoes pelo nome).
        $config = ['name' => $nome] + $this->app['config']["database.connections.{$nome}"];
        $chaveEscritor = self::CHAVE_ESCRITOR.$nome;

        // Instancia a PostgresConnection com o PDO emprestado (o construtor ja
        // aplica grammar/processor pgsql). configure() (do DatabaseManager pai)
        // injeta event dispatcher (DB::listen/circuit breaker), transaction
        // manager e reconnector — ConnectionFactory::createConnection e protected.
        $class = ($config['replica_routing'] ?? false)
            ? ConsistentPostgresConnection::class : PostgresConnection::class;
        $connection = new $class(
            $pdo,
            $config['database'] ?? '',
            $config['prefix'] ?? '',
            $config
        );

        if ($connection instanceof ConsistentPostgresConnection) {
            $this->ligarReplicas($nome, $connection, $config, $ctx);
        }

        $this->configure($connection, null);
        $connection->setReconnector(static function (Connection $connection) use ($pool, $ctx, $chaveEscritor): void {
            if (isset($ctx[$chaveEscritor])) {
                $pool->discard();
                unset($ctx[$chaveEscritor]);
            }
            $pdo = $pool->acquire();
            $ctx[$chaveEscritor] = $pdo;
            $connection->setPdo($pdo);
            $connection->setReadPdo(null);
        });

        return $connection;
    }

    /**
     * Leitores das replicas vem dos pools de leitura da propria conexao. Sem
     * pools (conexao sem replica_routing) nao ha leitor a ligar.
     */
    private function ligarReplicas(string $nome, ConsistentPostgresConnection $connection, array $config, ArrayAccess $ctx): void
    {
        $pools = $this->registry()->poolsDeLeitura($nome);
        if ($pools === []) {
            return;
        }

        $chaveLeitores = self::CHAVE_LEITORES.$nome;
        $chaveEscritor = self::CHAVE_ESCRITOR.$nome;
        // Prazo curto: pool de leitura cheio cai no proximo host/primario logo,
        // em vez de esperar o timeout inteiro do pool por host.
        $prazoLeitura = (float) ($config['read_timeout'] ?? 0.2);
        $writerPool = $this->pools[$nome];
        $this->proximaReplica[$nome] ??= random_int(0, count($pools) - 1);
        $connection->startReplicaAt($this->proximaReplica[$nome]++);
        $connection->setReadPdoConfig(array_replace($config, $config['read']));
        $connection->setKnownPrimaries($this->primarios[$nome] ??= new HostsPrimariosConhecidos);
        $connection->setWriterReleaser(static function (\PDO $pdo) use ($writerPool, $ctx, $chaveEscritor): \Closure {
            // So devolve o PDO que este contexto de fato emprestou.
            if (($ctx[$chaveEscritor] ?? null) === $pdo) {
                unset($ctx[$chaveEscritor]);
                $writerPool->release($pdo);
            }

            return static function () use ($writerPool, $ctx, $chaveEscritor): \PDO {
                $novo = $writerPool->acquire();
                $ctx[$chaveEscritor] = $novo;

                return $novo;
            };
        });
        $connection->setReplicaPdoResolver(
            static function (string $host) use ($pools, $ctx, $chaveLeitores, $prazoLeitura): \PDO {
                $readers = $ctx[$chaveLeitores] ?? [];
                if (! isset($readers[$host])) {
                    $readers[$host] = [$pools[$host], $pools[$host]->acquire($prazoLeitura)];
                    $ctx[$chaveLeitores] = $readers;
                }

                return $readers[$host][1];
            },
            static function (string $host) use ($ctx, $chaveLeitores): void {
                $readers = $ctx[$chaveLeitores] ?? [];
                if (isset($readers[$host])) {
                    $readers[$host][0]->discard();
                    unset($readers[$host]);
                    $ctx[$chaveLeitores] = $readers;
                }
            },
            static function (string $host) use ($ctx, $chaveLeitores): void {
                $readers = $ctx[$chaveLeitores] ?? [];
                if (isset($readers[$host])) {
                    [$pool, $pdo] = $readers[$host];
                    unset($readers[$host]);
                    $ctx[$chaveLeitores] = $readers;
                    $pool->release($pdo);
                }
            },
        );
    }
}
