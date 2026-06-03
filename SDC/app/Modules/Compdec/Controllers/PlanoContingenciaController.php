<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\PlanoContingenciaDTO;
use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Requests\StorePlanoContingenciaRequest;
use App\Modules\Compdec\Requests\UpdatePlanoContingenciaRequest;
use App\Modules\Compdec\Resources\PlanoContingenciaIndexResource;
use App\Modules\Compdec\Resources\PlanoContingenciaResource;
use App\Modules\Compdec\Services\PlanoContingenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanoContingenciaController extends Controller
{
    public function __construct(
        private readonly PlanoContingenciaService $service,
    ) {}

    public function index(Orgao $orgao): JsonResponse
    {
        $this->authorize('viewAny', CompdecPlanoContingencia::class);

        $planos = $this->service->listarPorOrgao($orgao->id);

        return PlanoContingenciaIndexResource::collection($planos)->response();
    }

    public function show(Orgao $orgao, CompdecPlanoContingencia $plano): JsonResponse
    {
        $this->authorize('view', $plano);

        return response()->json([
            'data' => PlanoContingenciaResource::make($plano->loadMissing('aprovador')),
        ]);
    }

    public function store(StorePlanoContingenciaRequest $request, Orgao $orgao): RedirectResponse
    {
        $arquivo = $request->file('arquivo');

        $this->service->criar(
            $orgao->id,
            PlanoContingenciaDTO::fromRequest($request->validated()),
            $arquivo,
        );

        return back()->with('success', 'Plano de contingencia adicionado.');
    }

    public function update(UpdatePlanoContingenciaRequest $request, Orgao $orgao, CompdecPlanoContingencia $plano): RedirectResponse
    {
        $this->service->atualizar(
            $orgao->id,
            $plano->id,
            PlanoContingenciaDTO::fromRequest($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Plano atualizado.');
    }

    public function ativar(Orgao $orgao, CompdecPlanoContingencia $plano): RedirectResponse
    {
        $this->authorize('update', $plano);

        $this->service->ativar($orgao->id, $plano->id);

        return back()->with('success', "Plano {$plano->versao} ativado.");
    }

    public function aprovar(Request $request, Orgao $orgao, CompdecPlanoContingencia $plano): RedirectResponse
    {
        $this->authorize('aprovar', $plano);

        $aprovador = $request->user();
        if (! $aprovador) {
            abort(401, 'Usuario nao autenticado.');
        }

        $this->service->aprovar($orgao->id, $plano->id, $aprovador->id);

        return back()->with('success', "Plano {$plano->versao} aprovado.");
    }

    public function destroy(Orgao $orgao, CompdecPlanoContingencia $plano): RedirectResponse
    {
        $this->authorize('delete', $plano);

        $this->service->deletar($orgao->id, $plano->id);

        return back()->with('success', 'Plano removido.');
    }

    public function download(Orgao $orgao, CompdecPlanoContingencia $plano): StreamedResponse
    {
        $this->authorize('view', $plano);

        return $this->service->download($orgao->id, $plano->id);
    }
}
