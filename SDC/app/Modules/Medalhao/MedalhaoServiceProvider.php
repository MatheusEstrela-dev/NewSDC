<?php

declare(strict_types=1);

namespace App\Modules\Medalhao;

use App\Modules\Medalhao\Console\IngerirCommand;
use App\Modules\Medalhao\Console\RollupCommand;
use App\Modules\Medalhao\Contracts\ArquivadorBronze;
use App\Modules\Medalhao\Infrastructure\FlowParquetArquivador;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class MedalhaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IngestorRegistry::class);

        $this->app->bind(ArquivadorBronze::class, FlowParquetArquivador::class);
    }

    public function boot(): void
    {
        // As fontes sao registradas pelos providers de cada modulo de dominio
        // (ex.: SismosServiceProvider), mantendo o kernel agnostico de dominio.

        if ($this->app->runningInConsole()) {
            $this->commands([
                IngerirCommand::class,
                RollupCommand::class,
            ]);
        }
    }
}
