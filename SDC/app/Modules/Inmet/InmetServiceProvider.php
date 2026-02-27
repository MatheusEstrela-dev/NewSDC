<?php

declare(strict_types=1);

namespace App\Modules\Inmet;

use App\Modules\Inmet\Services\InmetApiClient;
use App\Modules\Inmet\Services\InmetService;
use Illuminate\Support\ServiceProvider;

class InmetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InmetApiClient::class);
        $this->app->singleton(InmetService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/inmet.php
    }
}
