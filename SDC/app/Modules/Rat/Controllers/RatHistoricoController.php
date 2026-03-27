<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Models\RatOcorrencia;
use Inertia\Response;
use Inertia\Inertia;

/**
 * Controller para Histórico de uma Ocorrência RAT.
 */
class RatHistoricoController extends Controller
{
    /**
     * Exibe o histórico de uma ocorrência.
     */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        return Inertia::render('Rat/Historico/Show', [
            'ocorrencia' => $ocorrencia,
        ]);
    }
}
