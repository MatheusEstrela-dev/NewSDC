<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Actions\Contracts\ActionConfigInterface;
use App\Core\Actions\Policies\ActionPolicy;
use App\Core\Actions\Services\ActionConfigService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para registrar o ActionConfigService e ActionPolicy.
 */
class ActionConfigServiceProvider extends ServiceProvider
{
    /**
     * Registra os servicos no container.
     */
    public function register(): void
    {
        $this->app->singleton(ActionConfigService::class, function () {
            return new ActionConfigService();
        });

        $this->app->alias(ActionConfigService::class, ActionConfigInterface::class);

        $this->app->singleton(ActionPolicy::class, function ($app) {
            return new ActionPolicy($app->make(ActionConfigService::class));
        });
    }

    /**
     * Bootstrap dos servicos.
     */
    public function boot(): void
    {
        $service = $this->app->make(ActionConfigService::class);

        $this->registerModuleConfigs($service);

        $this->registerGates();
    }

    /**
     * Registra as configuracoes de cada modulo.
     */
    private function registerModuleConfigs(ActionConfigService $service): void
    {
        $moduleConfigs = config('actions.modules', []);

        foreach ($moduleConfigs as $configClass) {
            if (class_exists($configClass)) {
                $service->registerModule(new $configClass());
            }
        }
    }

    /**
     * Registra os Gates de autorizacao para todas as acoes.
     */
    private function registerGates(): void
    {
        $this->app->booted(function () {
            $policy = $this->app->make(ActionPolicy::class);
            $policy->registerGates();
        });
    }
}
