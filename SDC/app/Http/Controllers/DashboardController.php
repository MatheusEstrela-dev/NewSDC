<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Dashboard\Services\DashboardStatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatisticsService $dashboardStats) {}

    public function index(Request $request): Response
    {
        $stats = $this->dashboardStats->getStats();

        return Inertia::render('Dashboard', array_merge($stats->toArray(), [
            // FORA do DTO de proposito: o DTO e cacheado por
            // Cache::flexible('dashboard.stats.full') e vale para todo mundo,
            // enquanto permissao e por usuario. Cachear a flag entregaria o
            // link da frota ao primeiro visitante que tivesse a permissao e
            // depois a todos os outros.
            'canVerFrota' => (bool) $request->user()?->can('plantao.viaturas.view'),
        ]));
    }
}
