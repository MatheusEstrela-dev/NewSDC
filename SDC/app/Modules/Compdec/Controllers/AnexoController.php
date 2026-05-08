<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\AnexoDTO;
use App\Modules\Compdec\Models\CompdecAnexo;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Requests\StoreAnexoRequest;
use App\Modules\Compdec\Requests\UpdateAnexoRequest;
use App\Modules\Compdec\Resources\AnexoIndexResource;
use App\Modules\Compdec\Resources\AnexoResource;
use App\Modules\Compdec\Services\AnexoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    public function __construct(
        private readonly AnexoService $service,
    ) {}

    public function index(Request $request, Orgao $orgao): JsonResponse
    {
        $this->authorize('viewAny', CompdecAnexo::class);

        $perPage = (int) $request->integer('per_page', 20);
        $filtros = $request->only(['tipo']);

        $anexos = $this->service->listarPorOrgao($orgao->id, $perPage, $filtros);

        return response()->json(AnexoIndexResource::collection($anexos));
    }

    public function show(Orgao $orgao, CompdecAnexo $anexo): JsonResponse
    {
        $this->authorize('view', $anexo);

        return response()->json([
            'data' => AnexoResource::make($anexo),
        ]);
    }

    public function store(StoreAnexoRequest $request, Orgao $orgao): RedirectResponse
    {
        $this->service->criar(
            $orgao->id,
            AnexoDTO::fromRequest($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Anexo legal cadastrado.');
    }

    public function update(UpdateAnexoRequest $request, Orgao $orgao, CompdecAnexo $anexo): RedirectResponse
    {
        $this->service->atualizar(
            $orgao->id,
            $anexo->id,
            AnexoDTO::fromRequest($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Anexo legal atualizado.');
    }

    public function destroy(Orgao $orgao, CompdecAnexo $anexo): RedirectResponse
    {
        $this->authorize('delete', $anexo);
        $this->service->deletar($orgao->id, $anexo->id);

        return back()->with('success', 'Anexo legal removido.');
    }

    public function download(Orgao $orgao, CompdecAnexo $anexo): StreamedResponse
    {
        $this->authorize('download', $anexo);

        return $this->service->download($orgao->id, $anexo->id);
    }
}
