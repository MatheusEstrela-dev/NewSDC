<?php

namespace App\Modules\PlanCon\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Application\UseCases\GetPlanConStatsUseCase;
use App\Modules\PlanCon\Application\UseCases\ListMunicipiosComPlanoUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipiosComPlanoController extends Controller
{
    public function __construct(
        private readonly ListMunicipiosComPlanoUseCase $listMunicipiosUseCase,
        private readonly GetPlanConStatsUseCase $getStatsUseCase
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $perPage = $request->input('per_page', 15);
        $result = $this->listMunicipiosUseCase->execute($perPage);
        $stats = $this->getStatsUseCase->execute();

        return Inertia::render('PlanCon/MunicipiosComPlano', [
            'municipios' => $result['data'],
            'pagination' => $result['pagination'],
            'stats' => $stats->toArray(),
        ]);
    }
}
