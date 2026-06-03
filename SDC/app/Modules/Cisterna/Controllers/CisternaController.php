<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\CisternaDTO;
use App\Modules\Cisterna\Models\Cisterna;
use App\Modules\Cisterna\Requests\StoreCisternaRequest;
use App\Modules\Cisterna\Requests\UpdateCisternaRequest;
use App\Modules\Cisterna\Resources\CisternaIndexResource;
use App\Modules\Cisterna\Resources\CisternaResource;
use App\Modules\Cisterna\Services\CisternaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CisternaController extends Controller
{
    public function __construct(
        private readonly CisternaService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Cisterna::class);

        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['status', 'tipo', 'municipio_id', 'search']);

        $cisternas = $this->service->listar($perPage, $filtros);

        return Inertia::render('Cisterna/Index', [
            'cisternas' => CisternaIndexResource::collection($cisternas),
            'statistics' => fn () => $this->service->obterEstatisticas(),
            'filters' => $filtros,
            'canManage' => $request->user()?->can('cisternas.create') ?? false,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Cisterna::class);

        return Inertia::render('Cisterna/Create', [
            'municipios' => [],
        ]);
    }

    public function store(StoreCisternaRequest $request): RedirectResponse
    {
        $cisterna = $this->service->criar(
            CisternaDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('cisternas.show', $cisterna->id)
            ->with('success', "Cisterna {$cisterna->nome} cadastrada com sucesso.");
    }

    public function show(Cisterna $cisterna, Request $request): Response
    {
        $this->authorize('view', $cisterna);

        $cisterna = $this->service->obter($cisterna->id);

        return Inertia::render('Cisterna/Show', [
            'cisterna' => CisternaResource::make($cisterna),
            'canManage' => $request->user()?->can('update', $cisterna) ?? false,
            'canDelete' => $request->user()?->can('delete', $cisterna) ?? false,
        ]);
    }

    public function edit(Cisterna $cisterna): Response
    {
        $this->authorize('update', $cisterna);

        return Inertia::render('Cisterna/Edit', [
            'cisterna' => CisternaResource::make($cisterna->load('municipio')),
            'municipios' => [],
        ]);
    }

    public function update(UpdateCisternaRequest $request, Cisterna $cisterna): RedirectResponse
    {
        $atualizada = $this->service->atualizar(
            $cisterna->id,
            CisternaDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('cisternas.show', $atualizada->id)
            ->with('success', "Cisterna {$atualizada->nome} atualizada.");
    }

    public function destroy(Cisterna $cisterna): RedirectResponse
    {
        $this->authorize('delete', $cisterna);

        $nome = $cisterna->nome;
        $this->service->deletar($cisterna->id);

        return redirect()
            ->route('cisternas.index')
            ->with('success', "Cisterna {$nome} excluida.");
    }
}
