<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Http\Requests\ReceiveRatBIRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocoloController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = RatOcorrencia::query()
            ->with(['relatosMorph.conteudo'])
            ->orderByDesc('created_at');

        if ($protocolo = $request->input('protocolo')) {
            $query->where('numero_bos', 'like', '%' . $protocolo . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($ano = $request->input('ano')) {
            $query->whereYear('created_at', $ano);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('created_at', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('created_at', '<=', $dataFim);
        }

        $perPage   = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => RatListResource::collection($paginator)->resolve(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $rat = RatOcorrencia::with(['relatosMorph.conteudo'])->find($id);

        if (!$rat) {
            return response()->json([
                'success' => false,
                'message' => 'Protocolo RAT nao encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ]);
    }

    public function receive(ReceiveRatBIRequest $request): JsonResponse
    {
        $rat = $this->writeService->createWithData([
            'dadosGerais' => $request->input('dados_gerais', []),
            'comunicacao' => $request->input('comunicacao', []),
            'local'       => $request->input('local', []),
            'endereco'    => $request->input('endereco', []),
            'recursos'    => $request->input('recursos', []),
            'envolvidos'  => $request->input('envolvidos', []),
            'vistoria'    => $request->input('vistoria', []),
            'finalize'    => (bool) $request->input('finalize', false),
        ]);

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ], 201);
    }
}
