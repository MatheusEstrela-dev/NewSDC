<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TdapDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tdap/Dashboard', [
            'kpis' => [
                'cronogramas_ativos'        => 0,
                'cronogramas_encerrados'    => 0,
                'm3_entregues_mes'          => 0,
                'prestadores_ativos'        => 0,
                'viagens_pendentes_validar' => 0,
            ],
        ]);
    }
}
