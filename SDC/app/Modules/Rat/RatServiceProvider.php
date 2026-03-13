<?php

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Modules\Rat\Config\RatActionsConfig;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // All services auto-resolve via Eloquent Models — no interface bindings needed.
    }

    public function boot(): void
    {
        $this->registerModuleActions();
    }

    /**
     * Registra as configuracoes de acoes do modulo RAT.
     */
    private function registerModuleActions(): void
    {
        if ($this->app->bound(ActionConfigService::class)) {
            $actionConfigService = $this->app->make(ActionConfigService::class);
            $actionConfigService->registerModule(new RatActionsConfig());
        }
    }
}

