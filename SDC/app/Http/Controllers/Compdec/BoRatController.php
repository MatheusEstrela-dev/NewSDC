<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\DTOs\Rat\RatBoDTO;
use App\DTOs\Rat\RatOcorrenciaFiltroDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\BoRequest;
use App\Services\Rat\RatOcorrenciaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller para Boletim de Ocorrência (BO) do módulo RAT.
 *
 * Responsável por receber, listar e registrar BOs associados
 * às ocorrências RAT de Defesa Civil.
 *
 * Métodos públicos: index · store
 */
class BoRatController extends Controller
{
    public function __construct(
        private readonly RatOcorrenciaService $service
    ) {}

    /**
     * Listagem de BOs registrados.
     */
    public function index(): Response
    {
        $filtro = RatOcorrenciaFiltroDTO::fromArray(
            array_filter(['numero_bos' => request('numero_bos')])
        );

        $bos = $this->service->paginate($filtro);

        return Inertia::render('Compdec/Rat/Bo/Index', [
            'bos'     => $bos,
            'filters' => request()->only(['numero_bos']),
        ]);
    }

    /**
     * Registra um novo BO e cria a ocorrência RAT associada.
     */
    public function store(BoRequest $request): RedirectResponse
    {
        $ocorrencia = $this->service->manageOcorrencia(
            RatBoDTO::fromArray($request->validated())
        );

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', "BO {$ocorrencia->numero_bos} registrado com sucesso!");
    }
}
