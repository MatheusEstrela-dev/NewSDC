<?php

namespace App\Modules\PlanCon\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Application\UseCases\GetPlanConStatsUseCase;
use Inertia\Inertia;
use Inertia\Response;

class PlanConIndexController extends Controller
{
    public function __construct(
        private readonly GetPlanConStatsUseCase $getStatsUseCase
    ) {
    }

    public function __invoke(): Response
    {
        $stats = $this->getStatsUseCase->execute();

        return Inertia::render('PlanCon/PlanConIndex', [
            'stats' => $stats->toArray(),
        ]);
    }
}
