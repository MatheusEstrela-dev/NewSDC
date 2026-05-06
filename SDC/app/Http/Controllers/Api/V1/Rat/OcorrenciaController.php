<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OcorrenciaController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = RatOcorrencia::with(['relatosMorph.conteudo', 'creator', 'ratAnexos'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        $paginated = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => RatResource::collection($paginated)->resolve(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dadosGerais' => 'nullable|array',
            'comunicacao' => 'nullable|array',
            'local'       => 'nullable|array',
            'endereco'    => 'nullable|array',
            'recursos'    => 'nullable|array',
            'envolvidos'  => 'nullable|array',
            'vistoria'    => 'nullable|array',
            'finalize'    => 'nullable|boolean',
        ]);

        $ocorrencia = $this->writeService->createWithData($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ocorrência criada com sucesso.',
            'data'    => (new RatResource($this->writeService->findById((string) $ocorrencia->id)))->resolve(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $ocorrencia = $this->writeService->findById($id);

        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrência não encontrada.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($ocorrencia))->resolve(),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ocorrencia = RatOcorrencia::find($id);

        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrência não encontrada.'], 404);
        }

        if ($request->has('dadosGerais') || $request->has('comunicacao') || $request->has('local') || $request->has('endereco')) {
            $this->writeService->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($request->all()));
        }

        if ($request->has('recursos')) {
            foreach ($request->input('recursos', []) as $index => $r) {
                $r['seq'] = $r['seq'] ?? ($index + 1);
                $this->writeService->saveRecurso($id, RatRecursoDTO::fromArray($r));
            }
        }

        if ($request->has('envolvidos')) {
            foreach ($request->input('envolvidos', []) as $index => $e) {
                $e['seq'] = $e['seq'] ?? ($index + 1);
                $this->writeService->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($e));
            }
        }

        if ($request->has('vistoria') && !empty($request->input('vistoria'))) {
            $this->writeService->saveVistoria($id, RatVistoriaDTO::fromArray($request->input('vistoria')));
        }

        $ocorrencia->update(['updated_by' => auth()->id()]);

        return response()->json([
            'success' => true,
            'message' => 'Ocorrência atualizada.',
            'data'    => (new RatResource($this->writeService->findById($id)))->resolve(),
        ]);
    }

    public function finalize(Request $request, string $id): JsonResponse
    {
        $ocorrencia = $this->writeService->finalize($id);

        return response()->json([
            'success' => true,
            'message' => 'Ocorrência finalizada.',
            'data'    => (new RatResource($ocorrencia))->resolve(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $ocorrencia = RatOcorrencia::find($id);

        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrência não encontrada.'], 404);
        }

        $ocorrencia->delete();

        return response()->json(['success' => true, 'message' => 'Ocorrência excluída.']);
    }
}
