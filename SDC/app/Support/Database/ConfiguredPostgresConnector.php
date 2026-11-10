<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

/** Aplica o mesmo contrato de leitura no Laravel e no pool do Swoole. */
final class ConfiguredPostgresConnector implements ConnectorInterface
{
    public function connect(array $config): PDO
    {
        $readonly = filter_var($config['read_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $connector = $readonly ? new ReadonlyPostgresConnector : new PostgresConnector;

        return $connector->connect($config);
    }
}
