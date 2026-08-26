<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\ViaturaListDTO;
use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ViaturaIndexController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'localizacao', 'ativo', 'search']);

        $viaturas = $this->viaturaService->list($filters, 15);
        $user = $request->user();

        return Inertia::render('Plantao/ViaturasIndex', [
            'viaturas' => [
                'data' => ViaturaListDTO::collection($viaturas->items()),
                'pagination' => [
                    'current_page' => $viaturas->currentPage(),
                    'per_page' => $viaturas->perPage(),
                    'total' => $viaturas->total(),
                    'last_page' => $viaturas->lastPage(),
                    'from' => $viaturas->firstItem(),
                    'to' => $viaturas->lastItem(),
                ],
            ],
            'statistics' => $this->viaturaService->getStatistics(),
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusViatura::toSelectArray(),
                'localizacoes' => LocalizacaoViatura::toSelectArray(),
                'niveis' => NivelCombustivel::toSelectArray(),
            ],
            'canCreate' => (bool) $user?->can('plantao.viaturas.create'),
            'canEdit' => (bool) $user?->can('plantao.viaturas.edit'),
            'canDelete' => (bool) $user?->can('plantao.viaturas.delete'),
        ]);
    }
}
