<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Services\DashboardStatisticsService;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardStatisticsService::class);
    }
}
