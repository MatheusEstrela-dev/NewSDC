<?php

declare(strict_types=1);

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Modules\Rat\Config\RatActionsConfig;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use App\Modules\Rat\Services\RatAnexoService;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportBIService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatHistoricoService;
use App\Modules\Rat\Services\RatOcorrenciaService;
use App\Modules\Rat\Services\RatProtocoloService;
use App\Modules\Rat\Services\RatReceiveBIService;
use App\Modules\Rat\Services\RatRelatoService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RatRepositoryInterface::class,
            EloquentRatRepository::class
        );

        $this->app->singleton(RatProtocoloService::class);
        $this->app->singleton(RatWriteService::class);
        $this->app->singleton(RatExportService::class);
        $this->app->singleton(RatAttachmentService::class);
        $this->app->singleton(RatAnexoService::class);
        $this->app->singleton(RatOcorrenciaService::class);
        $this->app->singleton(RatHistoricoService::class);
        $this->app->singleton(RatRelatoService::class);
        $this->app->singleton(RatExportBIService::class);
        $this->app->singleton(RatReceiveBIService::class);
    }

    public function boot(): void
    {
        $this->registerModuleActions();
    }

    private function registerModuleActions(): void
    {
        if ($this->app->bound(ActionConfigService::class)) {
            $this->app->make(ActionConfigService::class)
                ->registerModule(new RatActionsConfig());
        }
    }
}
