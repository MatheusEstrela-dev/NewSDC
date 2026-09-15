<?php

declare(strict_types=1);

namespace App\Modules\Sismos;

use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Sismos\Ingestores\UnbObsisIngestor;
use App\Modules\Sismos\Ingestores\UspFdsnIngestor;
use App\Modules\Sismos\Normalizadores\FdsnTextNormalizador;
use App\Modules\Sismos\Normalizadores\ObsisCsvNormalizador;
use App\Modules\Sismos\Repositories\SismoRepository;
use Illuminate\Support\ServiceProvider;

class SismosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SismoRepository::class);
    }

    public function boot(): void
    {
        // O dominio registra as proprias fontes; o kernel do medalhao nao as
        // conhece.
        $registry = $this->app->make(IngestorRegistry::class);

        $registry->registrar(
            $this->app->make(UspFdsnIngestor::class),
            $this->app->make(FdsnTextNormalizador::class),
        );

        $registry->registrar(
            $this->app->make(UnbObsisIngestor::class),
            $this->app->make(ObsisCsvNormalizador::class),
        );
    }
}
