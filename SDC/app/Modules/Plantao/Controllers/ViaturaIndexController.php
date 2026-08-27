<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Plantao\DTOs\ViaturaListDTO;
use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Plantao;
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
            // SelectInput le value/id e label/name/text: a colecao crua de users
            // renderizaria o objeto inteiro na option, entao o mapeamento e feito aqui.
            'condutores' => User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(User $u) => ['value' => $u->id, 'label' => $u->name])
                ->all(),
            // Amarra a saida ao turno de quem esta de servico: sem isso
            // plantao_viatura_movimentacoes.plantao_id nascia sempre NULL e se
            // perdia a resposta a "quem estava de servico quando a viatura
            // avariou" (spec 1.1). Dado irrecuperavel depois.
            'plantaoAtivoId' => $this->plantaoAtivoId(),
            'canCreate' => (bool) $user?->can('plantao.viaturas.create'),
            'canEdit' => (bool) $user?->can('plantao.viaturas.edit'),
            'canDelete' => (bool) $user?->can('plantao.viaturas.delete'),
        ]);
    }

    /**
     * Turno ATIVO mais recente, ou null. Nulo e caso legitimo: registrar saida
     * nao exige turno aberto - a amarracao e melhor-esforco, nao guarda.
     */
    private function plantaoAtivoId(): ?int
    {
        $id = Plantao::query()
            ->where('status', StatusPlantao::ATIVO->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
