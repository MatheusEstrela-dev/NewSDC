<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Resources\TreinamentoListResource;
use App\Modules\Treinamento\Services\TreinamentoService;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoIndexController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo', 'categoria']);
        $treinamentos = $this->treinamentoService->list($filters, 15);
        $statistics = $this->treinamentoService->getStatistics();

        return Inertia::render('Treinamento/TreinamentoIndex', [
            'treinamentos' => TreinamentoListResource::collection($treinamentos->withPath($request->url())),
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusTreinamento::toSelectArray(),
                'tipos' => TipoTreinamento::toSelectArray(),
                'categorias' => CategoriaTreinamento::toSelectArray(),
            ],
        ]);
    }
}
