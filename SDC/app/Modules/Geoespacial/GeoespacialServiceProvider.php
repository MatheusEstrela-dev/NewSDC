<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial;

use App\Modules\Geoespacial\Normalizadores\GeoKmlNormalizador;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class GeoespacialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KmlExtrator::class);
        $this->app->singleton(GeoCamadaRepository::class);
    }

    public function boot(): void
    {
        // Fonte so-push: o conteudo chega por upload, entao nao ha ingestor a
        // registrar. Ver IngestorRegistry::registrarPush().
        $this->app->make(IngestorRegistry::class)->registrarPush(
            'geo-upload',
            'geoespacial',
            $this->app->make(GeoKmlNormalizador::class),
        );
    }
}
