<?php

namespace App\Modules\PlanCon\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Application\UseCases\GetPlanConStatsUseCase;
use App\Modules\PlanCon\Application\UseCases\ListMunicipiosSemPlanoUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipiosSemPlanoController extends Controller
{
    public function __construct(
        private readonly ListMunicipiosSemPlanoUseCase $listMunicipiosUseCase,
        private readonly GetPlanConStatsUseCase $getStatsUseCase
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $perPage = $request->input('per_page', 15);
        $result = $this->listMunicipiosUseCase->execute($perPage);
        $stats = $this->getStatsUseCase->execute();

        return Inertia::render('PlanCon/MunicipiosSemPlano', [
            'municipios' => $result['data'],
            'pagination' => $result['pagination'],
            'stats' => $stats->toArray(),
        ]);
    }
}
