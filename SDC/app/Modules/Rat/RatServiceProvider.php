<?php

declare(strict_types=1);

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Modules\Rat\Config\RatActionsConfig;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatQueryService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo RAT
 */
class RatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RatWriteService::class);
        $this->app->singleton(RatQueryService::class);
        $this->app->singleton(RatStatisticsService::class);
        $this->app->singleton(RatExportService::class);
    }

    /**
     * Bootstrap services.
     *
     * NOTA: As rotas são carregadas via routes/web.php -> routes/modules/rat.php
     */
    public function boot(): void
    {
        $this->registerModuleActions();
    }

    private function registerModuleActions(): void
    {
        if ($this->app->bound(ActionConfigService::class)) {
            $actionConfigService = $this->app->make(ActionConfigService::class);
            $actionConfigService->registerModule(new RatActionsConfig());
        }
    }
}
