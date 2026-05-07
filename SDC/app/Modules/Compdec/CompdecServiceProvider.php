<?php

declare(strict_types=1);

namespace App\Modules\Compdec;

use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Observers\CompdecEquipeObserver;
use App\Modules\Compdec\Services\EquipeService;
use App\Modules\Compdec\Services\OrgaoService;
use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Support\ServiceProvider;

class CompdecServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrgaoService::class);
        $this->app->singleton(PrefeituraService::class);
        $this->app->singleton(EquipeService::class);
    }

    public function boot(): void
    {
        // Observers (cache derivado em compdec_orgaos)
        CompdecEquipe::observe(CompdecEquipeObserver::class);

        // Rotas carregadas via routes/web.php (require routes/modules/compdec.php)
        // Policies registradas em AuthServiceProvider
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder
    }
}
