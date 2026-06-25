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
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmdaPlanoController extends Controller
{
    public function __construct(
        private readonly PmdaPlanoService $service,
        private readonly PmdaCopiaService $copia,
        private readonly PlanoPontoService $pontos,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'status', 'municipio_id', 'data_inicio', 'data_fim']);

        return Inertia::render('Pmda/Index', [
            'planos'  => PmdaPlanoListResource::collection($this->service->listar($filtros)),
            'filtros'      => $filtros,
            'statistics'   => [
                'total'     => \App\Modules\Pmda\Models\PmdaPlano::count(),
                'emEdicao'  => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::RASCUNHO->value)->count(),
                'emAnalise' => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::EM_ANALISE->value)->count(),
                'aprovados' => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::APROVADO->value)->count(),
            ],
            'statusOpcoes' => collect(\App\Modules\Pmda\Enums\PmdaStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->getLabel()])->values(),
            'municipios' => \App\Models\Municipio::query()
                ->orderBy('nome')->get(['id', 'nome', 'uf'])
                ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $municipioId = (int) $request->query('municipio_id');
        $municipio = \App\Models\Municipio::find($municipioId);

        if ($municipio === null) {
            return to_route('pmda.planos.index')->withErrors(['municipio_id' => 'Selecione um município válido.']);
        }

        $pendente = \App\Modules\Pmda\Models\PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereIn('status', PmdaPlanoService::statusPendente())
            ->first();

        if ($pendente !== null) {
            return to_route('pmda.planos.index')->withErrors([
                'municipio_id' => 'Este município já possui um PMDA em aberto ('.$pendente->status->getLabel().
                    ', protocolo '.($pendente->protocolo ?? '—').').',
            ]);
        }

        return Inertia::render('Pmda/Create', [
            'municipio' => ['id' => $municipio->id, 'nome' => $municipio->nome, 'uf' => $municipio->uf],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->service->exportar($request->only(['municipio_id', 'status']));
        $filename = 'pmda_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            if (! empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }
            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(StorePmdaPlanoRequest $request): RedirectResponse
    {
        try {
            $plano = $this->service->criar(
                municipioId: (int) $request->validated('municipio_id'),
                userId: (int) $request->user()->id,
                data: collect($request->validated())->except('municipio_id')->toArray(),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['municipio_id' => $e->getMessage()]);
        }

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
        try {
            $copia = $this->copia->copiar($plano, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['copiar' => $e->getMessage()]);
        }

        return to_route('pmda.planos.edit', $copia->id)->with('success', 'Cópia criada.');
    }
}
