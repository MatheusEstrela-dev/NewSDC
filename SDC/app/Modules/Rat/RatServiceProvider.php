<?php

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Config\RatActionsConfig;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RatRepositoryInterface::class,
            EloquentRatRepository::class
        );

        $this->app->singleton(RatService::class, function ($app) {
            return new RatService(
                $app->make(RatRepositoryInterface::class)
            );
        });
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

