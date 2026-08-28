<?php

declare(strict_types=1);

namespace App\Modules\Plantao;

use App\Modules\Plantao\Services\EscalaService;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use App\Modules\Plantao\Services\PassagemServicoService;
use App\Modules\Plantao\Services\PlantaoService;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Support\ServiceProvider;

class PlantaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlantaoService::class);
        $this->app->singleton(ViaturaService::class);
        $this->app->singleton(MovimentacaoViaturaService::class);
        $this->app->singleton(PassagemServicoService::class);
        $this->app->singleton(RelatorioPassagemService::class);
        $this->app->singleton(EscalaService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/plantao.php
    }
}
