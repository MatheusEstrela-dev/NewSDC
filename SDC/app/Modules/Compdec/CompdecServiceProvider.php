<?php

declare(strict_types=1);

namespace App\Modules\Compdec;

use App\Modules\Compdec\Services\OrgaoService;
use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Support\ServiceProvider;

class CompdecServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrgaoService::class);
        $this->app->singleton(PrefeituraService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php (require routes/modules/compdec.php)
        // Policies registradas em AuthServiceProvider
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder
    }
}
