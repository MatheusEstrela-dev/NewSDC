<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard principal com métricas consolidadas.
     */
    public function index(): Response
    {
        // TODO: Futuramente, obter estes dados de serviços reais de cada módulo (RAT, Ajuda Humanitária, etc.)
        // Por enquanto, simulamos os dados dinâmicos aqui no backend para tirar o hardcode do frontend.

        $radarMetrics = [
            ['label' => 'Eficiência', 'value' => rand(80, 95), 'fullMark' => 100],
            ['label' => 'Tempo Resp.', 'value' => rand(65, 85), 'fullMark' => 100],
            ['label' => 'Volume', 'value' => rand(85, 98), 'fullMark' => 100],
            ['label' => 'Qualidade', 'value' => rand(88, 96), 'fullMark' => 100],
            ['label' => 'Satisfação', 'value' => rand(75, 90), 'fullMark' => 100],
            ['label' => 'Cobertura', 'value' => rand(90, 100), 'fullMark' => 100],
        ];

        return Inertia::render('Dashboard', [
            'radarMetrics' => $radarMetrics,
        ]);
    }
}
