<?php

declare(strict_types=1);

namespace App\Modules\Cisterna;

use App\Modules\Cisterna\Services\CisternaService;
use Illuminate\Support\ServiceProvider;

class CisternaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CisternaService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php (require routes/modules/cisterna.php)
        // Policy registrada em AuthServiceProvider
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder
    }
}
