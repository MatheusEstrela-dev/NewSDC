<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\LoteDTO;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StoreLoteRequest;
use App\Modules\Tdap\Requests\UpdateLoteRequest;
use App\Modules\Tdap\Resources\LoteIndexResource;
use App\Modules\Tdap\Resources\LoteResource;
use App\Modules\Tdap\Services\LoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoteController extends Controller
{
    public function __construct(
        private readonly LoteService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['ativo', 'ata_id', 'municipio_id', 'prestador_id']);

        $lotes = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Lotes/Index', [
            'lotes'        => LoteIndexResource::collection($lotes),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'atas'         => fn () => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero']),
            'prestadores'  => fn () => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.lotes.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.lotes.edit') ?? false,
            'canDelete'    => $request->user()?->can('tdap.lotes.delete') ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Tdap/Lotes/Create', [
            'ata_id'      => $request->integer('ata_id') ?: null,
            'atas'        => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero', 'dt_inicio', 'dt_final']),
            'municipios'  => Municipio::orderBy('nome')->get(['id', 'nome', 'uf']),
            'prestadores' => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
        ]);
    }

    public function store(StoreLoteRequest $request): RedirectResponse
    {
        $lote = $this->service->criar(LoteDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.lotes.show', $lote->id)
            ->with('success', "Lote {$lote->numero} cadastrado.");
    }

    public function show(Lote $lote, Request $request): Response
    {
        $lote = $this->service->obter($lote->id);

        return Inertia::render('Tdap/Lotes/Show', [
            'lote'      => LoteResource::make($lote),
            'canEdit'   => $request->user()?->can('tdap.lotes.edit') ?? false,
            'canDelete' => $request->user()?->can('tdap.lotes.delete') ?? false,
        ]);
    }

    public function edit(Lote $lote): Response
    {
        return Inertia::render('Tdap/Lotes/Edit', [
            'lote'        => LoteResource::make($lote->load(['ata', 'municipio', 'prestador'])),
            'atas'        => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero', 'dt_inicio', 'dt_final']),
            'municipios'  => Municipio::orderBy('nome')->get(['id', 'nome', 'uf']),
            'prestadores' => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
        ]);
    }

    public function update(UpdateLoteRequest $request, Lote $lote): RedirectResponse
    {
        $atualizado = $this->service->atualizar($lote->id, LoteDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.lotes.show', $atualizado->id)
            ->with('success', "Lote {$atualizado->numero} atualizado.");
    }

    public function destroy(Lote $lote, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.lotes.delete')) {
            abort(403);
        }

        $numero = $lote->numero;
        $this->service->deletar($lote->id);

        return redirect()
            ->route('tdap.lotes.index')
            ->with('success', "Lote {$numero} excluido.");
    }
}
