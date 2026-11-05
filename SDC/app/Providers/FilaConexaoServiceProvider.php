<?php

namespace App\Providers;

use App\Listeners\Queue\DesconectaConexoesOciosas;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\ServiceProvider;

/**
 * Liga o descarte de conexoes ociosas apenas em console (workers de fila).
 * Limite em queue.idle_disconnect_seconds (default 240s, abaixo dos 5m do HAProxy).
 */
class FilaConexaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DesconectaConexoesOciosas::class, fn ($app) => new DesconectaConexoesOciosas(
            $app->make(DatabaseManager::class),
            (int) config('queue.idle_disconnect_seconds', 240),
        ));
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $events = $this->app['events'];
        $listener = $this->app->make(DesconectaConexoesOciosas::class);

        $events->listen(QueryExecuted::class, [$listener, 'registrarAtividade']);
        $events->listen([Looping::class, JobProcessing::class], [$listener, 'handle']);
    }
}
