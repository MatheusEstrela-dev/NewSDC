<?php

declare(strict_types=1);

namespace App\Modules\Cisterna;

use Illuminate\Support\ServiceProvider;

class CisternaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // O singleton do CisternaService do scaffold saiu junto com a classe.
        // Os services do dominio novo entram nas tasks seguintes.
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php (require routes/modules/cisterna.php)
        // Policies registradas em AuthServiceProvider
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder
    }
}
