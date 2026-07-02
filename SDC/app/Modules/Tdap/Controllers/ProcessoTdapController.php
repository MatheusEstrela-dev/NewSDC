<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\ProcessoTdapDTO;
use App\Modules\Tdap\DTOs\TransicaoProcessoDTO;
use App\Modules\Tdap\Enums\EstadoProcesso;
use App\Modules\Tdap\Enums\SwimlaneProcesso;
use App\Modules\Tdap\Models\ProcessoTdap;
use App\Modules\Tdap\Requests\StoreProcessoTdapRequest;
use App\Modules\Tdap\Requests\TransitarProcessoTdapRequest;
use App\Modules\Tdap\Resources\ProcessoTdapIndexResource;
use App\Modules\Tdap\Resources\ProcessoTdapResource;
use App\Modules\Tdap\Services\ProcessoTdapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessoTdapController extends Controller
{
    public function __construct(
        private readonly ProcessoTdapService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 20);
        $filtros = $request->only(['estado', 'swimlane', 'municipio_id', 'search']);

        $processos = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Processos/Index', [
            'processos'    => ProcessoTdapIndexResource::collection($processos),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'estados'      => EstadoProcesso::options(),
            'swimlanes'    => SwimlaneProcesso::options(),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.processos.create') ?? false,
            'canTransitar' => $request->user()?->can('tdap.processos.transitar') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['estado', 'swimlane', 'municipio_id', 'search']);
        $data = $this->service->exportar($filtros);

        $filename = 'processos_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para Excel reconhecer acentuacao.
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if (! empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }

            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function swimlanes(Request $request): Response
    {
        // Kanban: agrupa processos abertos por swimlane
        $processos = ProcessoTdap::query()
            ->abertos()
            ->with(['municipio:id,nome,uf'])
            ->orderByDesc('aberto_em')
            ->get()
            ->groupBy(fn ($p) => $p->swimlane_atual->value);

        $boards = collect(SwimlaneProcesso::cases())->map(fn (SwimlaneProcesso $s) => [
            'value'  => $s->value,
            'label'  => $s->label(),
            'cor'    => $s->cor(),
            'items'  => collect($processos->get($s->value, collect()))->map(fn ($p) => [
                'id'             => $p->id,
                'numero'         => $p->numero,
                'estado'         => $p->estado->value,
                'estado_label'   => $p->estado->label(),
                'municipio_nome' => $p->municipio?->nome,
                'municipio_uf'   => $p->municipio?->uf,
                'aberto_em'      => $p->aberto_em?->toIso8601String(),
            ])->values()->toArray(),
        ])->values()->toArray();

        return Inertia::render('Tdap/Processos/Swimlanes', [
            'boards' => $boards,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tdap/Processos/Create', [
            'municipios' => Municipio::catalogo(),
        ]);
    }

    public function store(StoreProcessoTdapRequest $request): RedirectResponse
    {
        $processo = $this->service->abrir(ProcessoTdapDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.processos.show', $processo->id)
            ->with('success', "Processo {$processo->numero} aberto em rascunho.");
    }

    public function show(ProcessoTdap $processo, Request $request): Response
    {
        $processo = $this->service->obter($processo->id);

        return Inertia::render('Tdap/Processos/Show', [
            'processo'     => ProcessoTdapResource::make($processo),
            'canTransitar' => $request->user()?->can('tdap.processos.transitar') ?? false,
        ]);
    }

    public function transitar(TransitarProcessoTdapRequest $request, ProcessoTdap $processo): RedirectResponse
    {
        [$_, $erro] = $this->service->transitar(
            $processo->id,
            TransicaoProcessoDTO::fromRequest($request->validated()),
        );

        if ($erro) {
            return back()->with('error', $erro);
        }

        return redirect()
            ->route('tdap.processos.show', $processo->id)
            ->with('success', 'Processo transitado.');
    }
}
