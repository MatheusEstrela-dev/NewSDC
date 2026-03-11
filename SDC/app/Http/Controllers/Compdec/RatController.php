<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\DTOs\Rat\RatBoDTO;
use App\DTOs\Rat\RatOcorrenciaFiltroDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\BoRequest;
use App\Models\Rat\RatOcorrencia;
use App\Services\Rat\RatOcorrenciaService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller principal CRUD do módulo RAT (nova estrutura).
 *
 * Responsabilidade única: coordena ações HTTP para RatOcorrencia.
 * Inversão de Dependência: depende de RatOcorrenciaService (serviço de domínio).
 *
 * Métodos públicos: index · create · store · show · edit · update · destroy · finalize · exportRats
 */
class RatController extends Controller
{
    public function __construct(
        private readonly RatOcorrenciaService $service,
        private readonly RatRelatoService $relatoService,
    ) {}

    /** Listagem paginada de ocorrências com filtros. */
    public function index(): Response
    {
        $filtro      = RatOcorrenciaFiltroDTO::fromArray(request()->only(['status', 'numero_bos']));
        $ocorrencias = $this->service->paginate($filtro);

        return Inertia::render('Compdec/Rat/Index', [
            'ocorrencias' => $ocorrencias,
            'filters'     => request()->only(['status', 'numero_bos']),
        ]);
    }

    /** Formulário de criação de nova ocorrência. */
    public function create(): Response
    {
        return Inertia::render('Compdec/Rat/Create');
    }

    /** Persiste nova ocorrência e redireciona para visualização. */
    public function store(BoRequest $request): RedirectResponse
    {
        $ocorrencia = $this->service->manageOcorrencia(
            RatBoDTO::fromArray($request->validated())
        );

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência RAT criada com sucesso!');
    }

    /** Visualização detalhada de uma ocorrência. */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        $ocorrencia->load('relatosMorph');

        return Inertia::render('Compdec/Rat/Show', [
            'ocorrencia' => $ocorrencia,
        ]);
    }

    /** Formulário de edição. */
    public function edit(RatOcorrencia $ocorrencia): Response
    {
        $ocorrencia->load('relatosMorph');

        return Inertia::render('Compdec/Rat/Edit', [
            'ocorrencia' => $ocorrencia,
        ]);
    }

    /** Atualiza dados da ocorrência. */
    public function update(BoRequest $request, RatOcorrencia $ocorrencia): RedirectResponse
    {
        $this->service->manageOcorrencia(
            RatBoDTO::fromArray($request->validated()),
            $ocorrencia->id
        );

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência atualizada com sucesso!');
    }

    /**
     * Oculta a ocorrência via soft delete (dados preservados no banco).
     * Cascateia o soft delete para todos os relatos e conteúdos relacionados.
     */
    public function destroy(RatOcorrencia $ocorrencia): RedirectResponse
    {
        $ocorrencia->delete();

        return redirect()
            ->route('compdec.rat.index')
            ->with('success', 'Ocorrência ocultada com sucesso!');
    }

    /** Finaliza a ocorrência — muda status para Finalizado (1). */
    public function finalize(RatOcorrencia $ocorrencia): RedirectResponse
    {
        $this->service->finalizar($ocorrencia->id);

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }

    /** Exportação CSV de ocorrências filtradas. */
    public function exportRats(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtro   = RatOcorrenciaFiltroDTO::fromArray($request->only(['status', 'numero_bos']), 9999);
        $filename = 'rat-ocorrencias-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($filtro) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['ID', 'Nº BOS', 'Status', 'Prazo Edição', 'Criado em'], ';');

            $this->service->paginate($filtro)->each(function ($oc) use ($handle) {
                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos,
                    $oc->status === 0 ? 'Rascunho' : 'Finalizado',
                    $oc->prazo_edicao?->format('d/m/Y H:i'),
                    $oc->created_at?->format('d/m/Y H:i'),
                ], ';');
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
