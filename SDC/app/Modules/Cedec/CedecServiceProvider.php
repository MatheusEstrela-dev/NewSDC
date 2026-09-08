<?php

declare(strict_types=1);

namespace App\Modules\Cedec;

use App\Modules\Cedec\Console\ImportarPrefeiturasCommand;
use Illuminate\Support\ServiceProvider;

/**
 * O modulo Cedec nasce na fase 1 apenas com Console/. A fase 2 EXPANDE este mesmo
 * provider com rotas e policies -- nao criar um provider paralelo.
 */
class CedecServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportarPrefeiturasCommand::class,
            ]);
        }
    }
}
