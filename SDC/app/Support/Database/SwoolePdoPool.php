<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDO;
use RuntimeException;
use Swoole\Coroutine\Channel;

/**
 * Pool de conexoes PDO por worker para uso sob coroutines do Swoole.
 *
 * Por que existe: sob SWOOLE_HOOK_ALL, varias coroutines de um mesmo worker
 * podem rodar queries "ao mesmo tempo". O Eloquent usa UMA conexao PDO por
 * worker (singleton) — se duas coroutines a usarem juntas, o protocolo do
 * Postgres corrompe. Este pool da a cada coroutine uma conexao propria,
 * emprestada e devolvida via Channel (bloqueio cooperativo, coroutine-safe).
 *
 * Decisao de design: NAO usamos Swoole\Database\PDOConfig porque ele nao expoe
 * sslmode de forma confiavel, e o Postgres da Azure exige TLS. Montamos o DSN
 * pgsql completo (com sslmode/sslrootcert) a partir do config do Laravel e
 * criamos PDO direto. ATTR_PERSISTENT e sempre removido (persistente + coroutine
 * = vazamento de conexao entre coroutines).
 *
 * LIMITACOES (precisam de validacao em build Swoole real antes de producao):
 *  - hooking de pdo_pgsql sob coroutine depende da versao do Swoole; medir.
 *  - TLS da Azure no PDO do pool deve ser confirmado por load-test.
 */
final class SwoolePdoPool
{
    private Channel $channel;

    private int $created = 0;

    /**
     * @param  array<int,mixed>  $options
     */
    private function __construct(
        private readonly string $dsn,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly array $options,
        private readonly int $size,
    ) {
        $this->channel = new Channel($size);
    }

    /**
     * Constroi o pool a partir de uma conexao pgsql definida em config/database.
     */
    public static function fromConnection(string $connection = 'pgsql', int $size = 16): self
    {
        $config = config("database.connections.{$connection}");

        if (! is_array($config) || ($config['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException("SwoolePdoPool suporta apenas conexoes pgsql; '{$connection}' invalida.");
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '5432',
            $config['database'] ?? 'sdc',
        );

        if (! empty($config['sslmode'])) {
            $dsn .= ';sslmode='.$config['sslmode'];
        }
        if (! empty($config['sslrootcert'])) {
            $dsn .= ';sslrootcert='.$config['sslrootcert'];
        }

        $options = ($config['options'] ?? []) + [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        // Nunca persistente no pool: a conexao precisa pertencer a coroutine.
        unset($options[PDO::ATTR_PERSISTENT]);

        return new self(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $options,
            max(1, $size),
        );
    }

    public function available(): int
    {
        return $this->channel->length();
    }

    /**
     * Pre-cria as conexoes ate o teto (chamar no WorkerStarting). Move o custo
     * de abrir conexao/handshake TLS do burst do request para o boot do worker.
     * Falha de criacao interrompe sem derrubar (restantes sobem on-demand).
     */
    public function warm(): void
    {
        // Channel->push exige contexto de coroutine. O WorkerStarting pode nao
        // estar em coroutine -> embrulha num Coroutine\run quando preciso.
        $fill = function (): void {
            while ($this->created < $this->size) {
                try {
                    $pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
                } catch (\Throwable $e) {
                    break;
                }
                $this->created++;
                $this->channel->push($pdo);
            }
        };

        if (\Swoole\Coroutine::getCid() > 0) {
            $fill();
        } else {
            \Swoole\Coroutine\run($fill);
        }
    }

    /**
     * Empresta uma conexao do pool para a closure e a devolve ao final, mesmo
     * em caso de excecao. Retorna o que a closure retornar.
     */
    public function run(callable $fn): mixed
    {
        $pdo = $this->acquire();

        try {
            $result = $fn($pdo);
        } catch (\PDOException $e) {
            // Erro de PDO pode significar conexao morta (idle timeout da Azure,
            // server gone away). Nao devolve ao pool: descarta e libera o slot.
            $this->discard();
            throw $e;
        } catch (\Throwable $e) {
            $this->release($pdo);
            throw $e;
        }

        $this->release($pdo);

        return $result;
    }

    public function acquire(): PDO
    {
        // Cria sob demanda ate o teto; senao espera uma conexao ser devolvida.
        // A checagem + incremento sao contiguos (sem yield), seguros no
        // scheduler cooperativo do Swoole.
        if ($this->created < $this->size && $this->channel->isEmpty()) {
            $this->created++;

            try {
                return new PDO($this->dsn, $this->username, $this->password, $this->options);
            } catch (\Throwable $e) {
                // Rollback do contador: sem isso, falhas transitorias de conexao
                // esgotam a capacidade ate created>=size e o pop() trava o pool.
                $this->discard();
                throw $e;
            }
        }

        return $this->channel->pop();
    }

    public function release(PDO $pdo): void
    {
        // Conexao com transacao aberta nao pode voltar suja ao pool.
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (\Throwable $e) {
            $this->discard();

            return;
        }

        $this->channel->push($pdo);
    }

    /**
     * Libera um slot do pool sem devolver conexao (descarte). O contador volta
     * abaixo do teto, permitindo que uma nova conexao seja criada no lugar.
     */
    public function discard(): void
    {
        if ($this->created > 0) {
            $this->created--;
        }
    }
}
