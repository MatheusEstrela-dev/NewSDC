<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\CronogramaDTO;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\PontoCaptacao;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StoreCronogramaRequest;
use App\Modules\Tdap\Requests\UpdateCronogramaRequest;
use App\Modules\Tdap\Resources\CronogramaIndexResource;
use App\Modules\Tdap\Resources\CronogramaResource;
use App\Modules\Tdap\Services\CronogramaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CronogramaController extends Controller
{
    public function __construct(
        private readonly CronogramaService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['estado', 'ata_id', 'prestador_id', 'municipio_id', 'search']);

        $cronogramas = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Cronogramas/Index', [
            'cronogramas'  => CronogramaIndexResource::collection($cronogramas),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'atas'         => fn () => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero']),
            'prestadores'  => fn () => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.cronogramas.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canAtivar'    => $request->user()?->can('tdap.cronogramas.ativar') ?? false,
            'canDelete'    => $request->user()?->can('tdap.cronogramas.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['estado', 'ata_id', 'prestador_id', 'municipio_id', 'search', 'data_inicio', 'data_fim']);
        $data = $this->service->exportar($filtros);

        $filename = 'cronogramas_'.now()->format('Y-m-d_H-i-s').'.csv';

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

    public function create(): Response
    {
        return Inertia::render('Tdap/Cronogramas/Create', [
            'atas'           => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero', 'dt_inicio', 'dt_final']),
            'lotes'          => Lote::ativo()->with(['ata:id,numero', 'municipios:id,nome,uf', 'prestador:id,nome,cnpj'])->get(),
            'municipios'     => Municipio::catalogo(),
            'prestadores'    => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'pontosCaptacao' => PontoCaptacao::ativo()->orderBy('nome')->get(['id', 'nome', 'tipo', 'municipio_id']),
        ]);
    }

    public function store(StoreCronogramaRequest $request): RedirectResponse
    {
        $cronograma = $this->service->criar(CronogramaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.cronogramas.show', $cronograma->id)
            ->with('success', "Cronograma {$cronograma->numero} criado em rascunho. Aloque caminhoes para ativar.");
    }

    public function show(Cronograma $cronograma, Request $request): Response
    {
        $cronograma = $this->service->obter($cronograma->id);
        [$podeAtivar, $motivoBloqueio] = $this->service->podeAtivar($cronograma);

        return Inertia::render('Tdap/Cronogramas/Show', [
            'cronograma'       => CronogramaResource::make($cronograma),
            'podeAtivar'       => $podeAtivar,
            'motivoBloqueio'   => $motivoBloqueio,
            'canEdit'          => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canDelete'        => $request->user()?->can('tdap.cronogramas.delete') ?? false,
            'canAtivar'        => $request->user()?->can('tdap.cronogramas.ativar') ?? false,
            'canProrrogar'     => $request->user()?->can('tdap.cronogramas.prorrogar') ?? false,
            'canAlocarCaminhao' => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canValidarViagem' => $request->user()?->can('tdap.viagens.validar') ?? false,
        ]);
    }

    public function edit(Cronograma $cronograma): Response
    {
        return Inertia::render('Tdap/Cronogramas/Edit', [
            'cronograma'     => CronogramaResource::make($cronograma->load(['ata', 'lote', 'municipio', 'prestador'])),
            'atas'           => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero', 'dt_inicio', 'dt_final']),
            'lotes'          => Lote::ativo()->with(['ata:id,numero', 'municipios:id,nome,uf', 'prestador:id,nome,cnpj'])->get(),
            'municipios'     => Municipio::catalogo(),
            'prestadores'    => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'pontosCaptacao' => PontoCaptacao::ativo()->orderBy('nome')->get(['id', 'nome', 'tipo', 'municipio_id']),
        ]);
    }

    public function update(UpdateCronogramaRequest $request, Cronograma $cronograma): RedirectResponse
    {
        try {
            $atualizado = $this->service->atualizar($cronograma->id, CronogramaDTO::fromRequest($request->validated()));

            return redirect()
                ->route('tdap.cronogramas.show', $atualizado->id)
                ->with('success', "Cronograma {$atualizado->numero} atualizado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ativar(Cronograma $cronograma): RedirectResponse
    {
        try {
            $this->service->ativar($cronograma->id);

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} ativado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function encerrar(Request $request, Cronograma $cronograma): RedirectResponse
    {
        try {
            $obs = (string) $request->input('observacao', '');
            $this->service->encerrar($cronograma->id, $obs !== '' ? $obs : null);

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} encerrado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function prorrogar(Request $request, Cronograma $cronograma): RedirectResponse
    {
        $request->validate([
            'dt_inicio_prorrogacao' => ['required', 'date'],
            'dt_final_prorrogacao'  => ['required', 'date', 'after_or_equal:dt_inicio_prorrogacao'],
            'justificativa'         => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->prorrogar(
                $cronograma->id,
                (string) $request->input('dt_inicio_prorrogacao'),
                (string) $request->input('dt_final_prorrogacao'),
                $request->input('justificativa'),
            );

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} prorrogado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function arquivar(Cronograma $cronograma): RedirectResponse
    {
        $this->service->arquivar($cronograma->id);

        return back()->with('success', "Cronograma {$cronograma->numero} arquivado.");
    }

    public function desarquivar(Cronograma $cronograma): RedirectResponse
    {
        $this->service->desarquivar($cronograma->id);

        return back()->with('success', "Cronograma {$cronograma->numero} desarquivado.");
    }

    public function destroy(Cronograma $cronograma, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.cronogramas.delete')) {
            abort(403);
        }

        try {
            $numero = $cronograma->numero;
            $this->service->deletar($cronograma->id);

            return redirect()
                ->route('tdap.cronogramas.index')
                ->with('success', "Cronograma {$numero} excluido.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
