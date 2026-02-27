<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Services\PlanoContingenciaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanConController extends Controller
{
    public function __construct(
        private readonly PlanoContingenciaService $planoService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'municipio_id']);
        $planos = $this->planoService->list($filters, 15);
        $statistics = $this->planoService->getStatistics();

        return Inertia::render('PlanCon/PlanConIndex', [
            'planos' => $planos,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    public function stats(): Response
    {
        $statistics = $this->planoService->getStatistics();

        return Inertia::render('PlanCon/Stats', [
            'statistics' => $statistics,
        ]);
    }

    public function municipiosComPlano(): Response
    {
        $municipios = $this->planoService->getMunicipiosComPlano();

        return Inertia::render('PlanCon/MunicipiosComPlano', [
            'municipios' => $municipios,
        ]);
    }

    public function municipiosSemPlano(): Response
    {
        $municipios = $this->planoService->getMunicipiosSemPlano();

        return Inertia::render('PlanCon/MunicipiosSemPlano', [
            'municipios' => $municipios,
        ]);
    }
}
