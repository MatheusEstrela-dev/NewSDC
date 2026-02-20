<?php

namespace App\Modules\PlanCon\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Application\UseCases\GetPlanConStatsUseCase;
use Illuminate\Http\JsonResponse;

class PlanConStatsController extends Controller
{
    public function __construct(
        private readonly GetPlanConStatsUseCase $getStatsUseCase
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $stats = $this->getStatsUseCase->execute();

        return response()->json($stats->toArray());
    }
}
