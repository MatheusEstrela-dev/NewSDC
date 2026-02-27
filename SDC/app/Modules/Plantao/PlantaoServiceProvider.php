<?php

declare(strict_types=1);

namespace App\Modules\Plantao;

use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Support\ServiceProvider;

class PlantaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlantaoService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/plantao.php
    }
}
