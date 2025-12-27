<?php

namespace App\Modules\Tdap\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TdapDashboardController extends Controller
{
    /**
     * Dashboard do módulo TDAP
     */
    public function index(): Response
    {
        return Inertia::render('Tdap/Dashboard', [
            'statistics' => [
                'total_products' => 0,
                'total_recebimentos' => 0,
                'total_movimentacoes' => 0,
                'estoque_baixo' => 0,
            ],
        ]);
    }
}
