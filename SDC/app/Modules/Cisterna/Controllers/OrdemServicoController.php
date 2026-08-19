<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Requests\StoreOrdemServicoRequest;
use App\Modules\Cisterna\Requests\UpdateOrdemServicoRequest;
use App\Modules\Cisterna\Resources\OrdemServicoResource;
use App\Modules\Cisterna\Services\OrdemServicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrdemServicoController extends Controller
{
    public function __construct(
        private readonly OrdemServicoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        return Inertia::render('Cisterna/OrdensServico/Index', [
            'ordens' => OrdemServicoResource::collection($this->service->listar()),
            'lotes' => CisternaLote::query()->orderBy('nome')->get(['id', 'nome']),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaOrdemServico::class) ?? false,
            ],
        ]);
    }

    public function doLote(CisternaLote $lote, Request $request): Response
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        return Inertia::render('Cisterna/OrdensServico/Index', [
            'ordens' => OrdemServicoResource::collection($this->service->listar($lote->id)),
            'lote' => ['id' => $lote->id, 'nome' => $lote->nome],
            'lotes' => CisternaLote::query()->orderBy('nome')->get(['id', 'nome']),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaOrdemServico::class) ?? false,
            ],
        ]);
    }

    public function store(StoreOrdemServicoRequest $request): RedirectResponse
    {
        $os = $this->service->criar(OrdemServicoDTO::deValidados($request->validated()));

        if ($request->hasFile('documento_os')) {
            $os->addMedia($request->file('documento_os'))->toMediaCollection('documento_os');
        }

        return back()->with('success', "Ordem de servico {$os->nome} criada.");
    }

    /**
     * Historico do lote: quem entrou e quem saiu desta OS.
     */
    public function timeline(CisternaOrdemServico $ordemServico): JsonResponse
    {
        $this->authorize('history', $ordemServico);

        return response()->json($this->service->timeline($ordemServico));
    }

    public function update(UpdateOrdemServicoRequest $request, CisternaOrdemServico $ordemServico): RedirectResponse
    {
        $atualizada = $this->service->atualizar($ordemServico, OrdemServicoDTO::deValidados($request->validated()));

        if ($request->hasFile('documento_os')) {
            $atualizada->addMedia($request->file('documento_os'))->toMediaCollection('documento_os');
        }

        return back()->with('success', "Ordem de servico {$atualizada->nome} atualizada.");
    }

    public function destroy(CisternaOrdemServico $ordemServico): RedirectResponse
    {
        $this->authorize('delete', $ordemServico);

        $nome = $ordemServico->nome;
        $this->service->deletar($ordemServico);

        return back()->with('success', "Ordem de servico {$nome} excluida.");
    }
}
