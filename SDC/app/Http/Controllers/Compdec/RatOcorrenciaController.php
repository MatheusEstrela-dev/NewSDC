<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\DTOs\Rat\RatBoDTO;
use App\DTOs\Rat\RatOcorrenciaFiltroDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\RatDadosGeraisRequest;
use App\Models\Rat\RatOcorrencia;
use App\Services\Rat\RatOcorrenciaService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller de gerenciamento de ocorrências RAT com seus relatos.
 *
 * Coordinação entre RatOcorrenciaService e RatRelatoService,
 * expondo ações de criação, visualização e finalização.
 *
 * Métodos públicos: index · show · store · finalize
 */
class RatOcorrenciaController extends Controller
{
    public function __construct(
        private readonly RatOcorrenciaService $ocorrenciaService,
        private readonly RatRelatoService     $relatoService,
    ) {}

    /** Listagem de ocorrências com campo de pesquisa. */
    public function index(): Response
    {
        $filtro      = RatOcorrenciaFiltroDTO::fromArray(request()->only(['status', 'numero_bos']));
        $ocorrencias = $this->ocorrenciaService->paginate($filtro);

        return Inertia::render('Compdec/Rat/Ocorrencia/Index', [
            'ocorrencias' => $ocorrencias,
        ]);
    }

    /** Detalhe completo com relatos polimórficos. */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        return Inertia::render('Compdec/Rat/Ocorrencia/Show', [
            'ocorrencia' => $ocorrencia->load('relatosMorph'),
        ]);
    }

    /** Cria ocorrência e seus relatos iniciais em transação. */
    public function store(RatDadosGeraisRequest $request): RedirectResponse
    {
        $boDto      = RatBoDTO::fromArray($request->only(['numero_bos', 'historico', 'prazo_edicao', 'ocorrencia_origem_id']));
        $ocorrencia = $this->ocorrenciaService->manageOcorrencia($boDto);

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        return redirect()
            ->route('compdec.rat.ocorrencias.show', $ocorrencia->id)
            ->with('success', 'Ocorrência criada com sucesso!');
    }

    /** Finaliza a ocorrência. */
    public function finalize(RatOcorrencia $ocorrencia): RedirectResponse
    {
        $this->ocorrenciaService->finalizar($ocorrencia->id);

        return redirect()
            ->route('compdec.rat.ocorrencias.show', $ocorrencia->id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }
}
