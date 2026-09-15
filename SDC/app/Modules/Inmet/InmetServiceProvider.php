<?php

declare(strict_types=1);

namespace App\Modules\Inmet;

use App\Modules\Inmet\Ingestores\InmetApiIngestor;
use App\Modules\Inmet\Normalizadores\InmetJsonNormalizador;
use App\Modules\Inmet\Repositories\InmetRepository;
use App\Modules\Inmet\Services\InmetApiClient;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class InmetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InmetApiClient::class);
        $this->app->singleton(InmetRepository::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/inmet.php
        //
        // InmetService saiu: todos os seus metodos estavam sem chamador, e a
        // agregacao que ele fazia em PHP virou matview na camada Gold.

        // O dominio registra a propria fonte; o kernel do medalhao nao a conhece.
        $registry = $this->app->make(IngestorRegistry::class);

        $registry->registrar(
            $this->app->make(InmetApiIngestor::class),
            $this->app->make(InmetJsonNormalizador::class),
        );
    }
}
