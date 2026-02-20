<?php

namespace App\Modules\PlanCon;

use App\Modules\PlanCon\Domain\Repositories\PlanoContingenciaRepositoryInterface;
use App\Modules\PlanCon\Infrastructure\Persistence\EloquentPlanoContingenciaRepository;
use Illuminate\Support\ServiceProvider;

class PlanConServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PlanoContingenciaRepositoryInterface::class,
            EloquentPlanoContingenciaRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
