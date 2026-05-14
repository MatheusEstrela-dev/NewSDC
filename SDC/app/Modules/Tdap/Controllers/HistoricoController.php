<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Resources\HistoricoResource;
use App\Modules\Tdap\Services\HistoricoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoricoController extends Controller
{
    public function __construct(
        private readonly HistoricoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 25);
        $filtros = $request->only(['entity_type', 'entity_id', 'tipo_evento', 'user_id', 'de', 'ate']);

        $historicos = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Historicos/Index', [
            'historicos'   => HistoricoResource::collection($historicos),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'filtros'      => $filtros,
        ]);
    }

    public function show(Historico $historico): Response
    {
        return Inertia::render('Tdap/Historicos/Show', [
            'historico' => HistoricoResource::make($historico->load('user')),
        ]);
    }

    /**
     * Endpoint AJAX para timeline embutida em Show de outras entidades.
     */
    public function porEntidade(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string', 'max:60'],
            'entity_id'   => ['required', 'integer', 'min:1'],
        ]);

        $rows = Historico::query()
            ->doEntity((string) $request->input('entity_type'), (int) $request->integer('entity_id'))
            ->with('user:id,name')
            ->orderByDesc('data_evento')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => HistoricoResource::collection($rows)->resolve(),
        ]);
    }
}
