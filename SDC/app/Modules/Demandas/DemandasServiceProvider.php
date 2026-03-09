<?php

declare(strict_types=1);

namespace App\Modules\Demandas;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Demandas
 */
class DemandasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TaskService is auto-resolved by Laravel's container
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/demandas.php
    }
}
