<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\ReservaListDTO;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ReservaViaturaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservaIndexController extends Controller
{
    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'viatura_id', 'agente_id', 'de', 'ate']);
        $user = $request->user();

        // "Minhas reservas" e o recorte padrao: a tela existe primeiro para o
        // agente saber quando pega a chave, e so depois para a supervisao ver a
        // agenda inteira. Quem nao pode gerenciar nem enxerga o filtro.
        $podeGerenciar = (bool) $user?->can('plantao.reservas.manage');

        if (!$podeGerenciar) {
            $filters['agente_id'] = $user?->id;
        }

        $reservas = $this->reservaService->list($filters, 15);

        return Inertia::render('Plantao/ReservasIndex', [
            'reservas' => [
                'data' => ReservaListDTO::collection($reservas->items()),
                'pagination' => [
                    'current_page' => $reservas->currentPage(),
                    'per_page' => $reservas->perPage(),
                    'total' => $reservas->total(),
                    'last_page' => $reservas->lastPage(),
                    'from' => $reservas->firstItem(),
                    'to' => $reservas->lastItem(),
                ],
            ],
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusReserva::toSelectArray(),
            ],
            // Somente as ativas: reservar viatura inativa e recusado pelo
            // service, entao oferece-la no select so produziria erro.
            'viaturas' => Viatura::query()
                ->ativas()
                ->orderBy('prefixo')
                ->get(['id', 'prefixo', 'placa', 'modelo'])
                ->map(fn(Viatura $v) => [
                    'value' => $v->id,
                    'label' => trim($v->prefixo.' - '.$v->placa),
                ])
                ->all(),
            'agenteAtualId' => $user?->id,
            'canCreate' => (bool) $user?->can('plantao.reservas.create'),
            'canManage' => $podeGerenciar,
            'canScan' => (bool) $user?->can('plantao.viaturas.movimentar'),
        ]);
    }
}
