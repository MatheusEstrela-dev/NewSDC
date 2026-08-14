<?php

declare(strict_types=1);

namespace App\Modules\Cisterna;

use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Observers\CisternaVistoriaObserver;
use Illuminate\Support\ServiceProvider;

class CisternaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // O singleton do CisternaService do scaffold saiu junto com a classe.
        // Os services do dominio novo sao resolvidos por autowiring.
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php (require routes/modules/cisterna.php)
        // Policies registradas em AuthServiceProvider
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder

        // Avanca situacao_obra para `instalado` quando a vistoria do fornecedor
        // e gravada. No legado isso era efeito colateral dentro do controller
        // (CisternaController.php:1681); aqui vale tambem para o refino do ETL,
        // que nao passa por controller nenhum.
        CisternaVistoria::observe(CisternaVistoriaObserver::class);
    }
}
