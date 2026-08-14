<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\LoteDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Requests\StoreLoteRequest;
use App\Modules\Cisterna\Requests\UpdateLoteRequest;
use App\Modules\Cisterna\Resources\LoteResource;
use App\Modules\Cisterna\Services\LoteService;
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
        $this->authorize('viewAny', CisternaLote::class);

        return Inertia::render('Cisterna/Lotes/Index', [
            'lotes' => LoteResource::collection($this->service->listar()),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaLote::class) ?? false,
            ],
        ]);
    }

    public function store(StoreLoteRequest $request): RedirectResponse
    {
        $lote = $this->service->criar(LoteDTO::deValidados($request->validated()));

        return back()->with('success', "Lote {$lote->nome} criado.");
    }

    public function update(UpdateLoteRequest $request, CisternaLote $lote): RedirectResponse
    {
        $atualizado = $this->service->atualizar($lote, LoteDTO::deValidados($request->validated()));

        return back()->with('success', "Lote {$atualizado->nome} atualizado.");
    }

    public function destroy(CisternaLote $lote): RedirectResponse
    {
        $this->authorize('delete', $lote);

        $nome = $lote->nome;
        $this->service->deletar($lote);

        return back()->with('success', "Lote {$nome} excluido.");
    }
}
