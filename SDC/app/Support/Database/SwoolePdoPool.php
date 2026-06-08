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

    /**
     * Empresta uma conexao do pool para a closure e a devolve ao final, mesmo
     * em caso de excecao. Retorna o que a closure retornar.
     */
    public function run(callable $fn): mixed
    {
        $pdo = $this->acquire();

        try {
            return $fn($pdo);
        } finally {
            $this->release($pdo);
        }
    }

    private function acquire(): PDO
    {
        // Cria sob demanda ate o teto; senao espera uma conexao ser devolvida.
        // A checagem + incremento sao contiguos (sem yield), seguros no
        // scheduler cooperativo do Swoole.
        if ($this->created < $this->size && $this->channel->isEmpty()) {
            $this->created++;

            return new PDO($this->dsn, $this->username, $this->password, $this->options);
        }

        return $this->channel->pop();
    }

    private function release(PDO $pdo): void
    {
        $this->channel->push($pdo);
    }
}
