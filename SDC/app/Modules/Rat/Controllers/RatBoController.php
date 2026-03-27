<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Response;
use Inertia\Inertia;

/**
 * Controller para Boletim de Ocorrência (BO) RAT.
 */
class RatBoController extends Controller
{
    /**
     * Lista todos os boletins de ocorrência.
     */
    public function index(): Response
    {
        return Inertia::render('Rat/BO/Index');
    }

    /**
     * Cria um novo boletim de ocorrência.
     */
    public function store()
    {
        // Implementar logic para criar BO
    }
}
