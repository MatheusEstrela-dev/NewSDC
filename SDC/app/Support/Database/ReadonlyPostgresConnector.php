<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connectors\PostgresConnector;

final class ReadonlyPostgresConnector extends PostgresConnector
{
    public function connect(array $config)
    {
        $pdo = parent::connect($config);
        // Permanece somente leitura mesmo se o Patroni promover este servidor.
        $pdo->exec('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY');

        return $pdo;
    }
}
