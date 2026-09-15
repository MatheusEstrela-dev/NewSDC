<?php

declare(strict_types=1);

namespace App\Modules\Cemaden;

use App\Modules\Cemaden\Ingestores\CemadenPluviometriaIngestor;
use App\Modules\Cemaden\Normalizadores\CemadenJsonNormalizador;
use App\Modules\Cemaden\Repositories\CemadenRepository;
use App\Modules\Cemaden\Services\CemadenApiClient;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class CemadenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CemadenApiClient::class);
        $this->app->singleton(CemadenRepository::class);
    }

    public function boot(): void
    {
        // O dominio registra a propria fonte; o kernel do medalhao nao a conhece.
        $registry = $this->app->make(IngestorRegistry::class);

        $registry->registrar(
            $this->app->make(CemadenPluviometriaIngestor::class),
            $this->app->make(CemadenJsonNormalizador::class),
        );
    }
}
