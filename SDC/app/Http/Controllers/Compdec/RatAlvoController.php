<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\DTOs\Rat\RatOcorrenciaFiltroDTO;
use App\Http\Controllers\Controller;
use App\Models\Rat\RatOcorrencia;
use App\Services\Rat\RatOcorrenciaService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller de Alvos (endereços/locais de interesse) nas ocorrências RAT.
 *
 * Responsável por exibir o mapa e a listagem de alvos vinculados
 * a uma ocorrência RAT específica.
 *
 * Métodos públicos: index · show
 */
class RatAlvoController extends Controller
{
    public function __construct(
        private readonly RatOcorrenciaService $service
    ) {}

    /**
     * Listagem de alvos de todas as ocorrências abertas.
     */
    public function index(): Response
    {
        $filtro      = RatOcorrenciaFiltroDTO::fromArray(['status' => 0]);
        $ocorrencias = $this->service->paginate($filtro);

        return Inertia::render('Compdec/Rat/Alvo/Index', [
            'ocorrencias' => $ocorrencias,
        ]);
    }

    /**
     * Detalhes dos alvos de uma ocorrência específica.
     */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        return Inertia::render('Compdec/Rat/Alvo/Show', [
            'ocorrencia' => $ocorrencia,
        ]);
    }
}
