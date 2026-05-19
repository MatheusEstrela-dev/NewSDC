<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\PlantaoListDTO;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantaoIndexController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'periodo', 'search']);

        $plantoes = $this->plantaoService->list($filters, 15);
        $statistics = $this->plantaoService->getStatistics($filters);

        $plantoesData = [
            'data' => PlantaoListDTO::collection($plantoes->items()),
            'pagination' => [
                'current_page' => $plantoes->currentPage(),
                'per_page' => $plantoes->perPage(),
                'total' => $plantoes->total(),
                'last_page' => $plantoes->lastPage(),
                'from' => $plantoes->firstItem(),
                'to' => $plantoes->lastItem(),
            ],
        ];

        return Inertia::render('Plantao/PlantaoIndex', [
            'plantoes' => $plantoesData,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusPlantao::toSelectArray(),
                'periodos' => PeriodoPlantao::toSelectArray(),
            ],
        ]);
    }
}
