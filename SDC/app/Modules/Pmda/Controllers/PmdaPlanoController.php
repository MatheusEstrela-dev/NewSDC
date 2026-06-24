<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Requests\StorePmdaPlanoRequest;
use App\Modules\Pmda\Requests\UpdatePmdaPlanoRequest;
use App\Modules\Pmda\Resources\PmdaPlanoListResource;
use App\Modules\Pmda\Resources\PmdaPlanoResource;
use App\Modules\Pmda\Services\PlanoPontoService;
use App\Modules\Pmda\Services\PmdaCopiaService;
use App\Modules\Pmda\Services\PmdaPlanoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PmdaPlanoController extends Controller
{
    public function __construct(
        private readonly PmdaPlanoService $service,
        private readonly PmdaCopiaService $copia,
        private readonly PlanoPontoService $pontos,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Pmda/Index', [
            'planos'  => PmdaPlanoListResource::collection(
                $this->service->listar($request->only(['municipio_id', 'status']))
            ),
            'filtros' => $request->only(['municipio_id', 'status']),
        ]);
    }

    public function store(StorePmdaPlanoRequest $request): RedirectResponse
    {
        $plano = $this->service->criar(
            municipioId: (int) $request->validated('municipio_id'),
            userId: (int) $request->user()->id,
            data: [],
        );

        return to_route('pmda.planos.edit', $plano->id)->with('success', 'PMDA criado.');
    }

    public function edit(PmdaPlano $plano): Response
    {
        $plano->load(['municipio', 'comunidades.representantes', 'pontos'])
            ->loadCount('comunidades');

        return Inertia::render('Pmda/Edit', [
            'plano'             => new PmdaPlanoResource($plano),
            'pontos_disponiveis' => $this->pontos->disponiveis($plano)->map(fn ($p) => [
                'id'         => $p->id,
                'nome'       => $p->nome,
                'capacidade' => $p->capacidade,
            ])->values(),
        ]);
    }

    public function update(UpdatePmdaPlanoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->atualizar($plano, $request->validated(), (int) $request->user()->id);

        return back()->with('success', 'PMDA atualizado.');
    }

    public function copiar(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $copia = $this->copia->copiar($plano, (int) $request->user()->id);

        return to_route('pmda.planos.edit', $copia->id)->with('success', 'Cópia criada.');
    }
}
