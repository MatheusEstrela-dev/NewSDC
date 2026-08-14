<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Requests\StoreComunidadeRequest;
use App\Modules\Cisterna\Requests\UpdateComunidadeRequest;
use App\Modules\Cisterna\Resources\ComunidadeResource;
use App\Modules\Cisterna\Services\ComunidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComunidadeController extends Controller
{
    public function __construct(
        private readonly ComunidadeService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        $filtros = $request->only(['municipio_id', 'search', 'apenas_ativas']);

        return Inertia::render('Cisterna/Comunidades/Index', [
            'comunidades' => ComunidadeResource::collection($this->service->listar($filtros)),
            'filtros' => $filtros,
            'municipios' => Municipio::habilitadosCisterna(),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaComunidade::class) ?? false,
            ],
        ]);
    }

    /**
     * Select em cascata do formulario de beneficiario.
     */
    public function doMunicipio(int $municipio): JsonResponse
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        return response()->json($this->service->doMunicipio($municipio));
    }

    public function store(StoreComunidadeRequest $request): RedirectResponse
    {
        $comunidade = $this->service->criar(ComunidadeDTO::deValidados($request->validated()));

        return back()->with('success', "Comunidade {$comunidade->nome} cadastrada.");
    }

    public function update(UpdateComunidadeRequest $request, CisternaComunidade $comunidade): RedirectResponse
    {
        $atualizada = $this->service->atualizar($comunidade, ComunidadeDTO::deValidados($request->validated()));

        return back()->with('success', "Comunidade {$atualizada->nome} atualizada.");
    }

    public function destroy(CisternaComunidade $comunidade): RedirectResponse
    {
        $this->authorize('delete', $comunidade);

        $nome = $comunidade->nome;
        $this->service->deletar($comunidade);

        return back()->with('success', "Comunidade {$nome} excluida.");
    }
}
