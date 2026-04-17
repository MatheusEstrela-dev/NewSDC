<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Dashboard\Services\DashboardStatisticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatisticsService $dashboardStats) {}

    public function index(): Response
    {
        $stats = $this->dashboardStats->getStats();

        return Inertia::render('Dashboard', $stats->toArray());
    }
}
