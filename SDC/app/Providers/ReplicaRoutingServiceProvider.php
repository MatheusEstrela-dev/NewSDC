<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Database\ConsistentPostgresConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;

final class ReplicaRoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor('pgsql', static function ($pdo, $database, $prefix, $config) {
            return ($config['replica_routing'] ?? false)
                ? new ConsistentPostgresConnection($pdo, $database, $prefix, $config)
                : new PostgresConnection($pdo, $database, $prefix, $config);
        });
    }

    public function boot(): void
    {
        $this->app['events']->listen(JobProcessing::class, function (): void {
            if (! $this->app->resolved('db')) {
                return;
            }

            foreach ($this->app['db']->getConnections() as $connection) {
                if ($connection instanceof ConsistentPostgresConnection) {
                    $connection->forgetRecordModificationState();
                }
            }
        });
    }
}
