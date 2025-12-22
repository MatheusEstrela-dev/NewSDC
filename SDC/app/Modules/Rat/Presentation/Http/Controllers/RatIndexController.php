<?php

namespace App\Modules\Rat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\UseCases\GetRatStatisticsUseCase;
use App\Modules\Rat\Application\UseCases\ListRatsUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RatIndexController extends Controller
{
    public function __construct(
        private readonly GetRatStatisticsUseCase $getStatisticsUseCase,
        private readonly ListRatsUseCase $listRatsUseCase
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'protocolo',
            'status',
            'data_inicio',
            'data_fim',
            'ano',
            'municipio',
            'tipo_cobrade',
            'natureza',
            'criado_por',
        ]);

        $statistics = $this->getStatisticsUseCase->execute($filters);
        $rats = $this->listRatsUseCase->execute($filters, 15);

        // Transformar RATs para formato esperado pelo frontend
        $ratsData = $rats->map(function ($rat) {
            return [
                'id' => $rat->id,
                'protocolo' => $rat->protocolo,
                'status' => $rat->status,
                'created_at' => $rat->created_at->toDateTimeString(),
                'updated_at' => $rat->updated_at->toDateTimeString(),
                'local' => $rat->local ?? [],
                'dadosGerais' => $rat->dados_gerais ?? [],
                'recursos' => [],
                'envolvidos' => [],
                'anexos' => [],
                'criado_por' => $rat->created_by ?? 'Sistema',
            ];
        });

        return Inertia::render('RatIndex', [
            'statistics' => $statistics->toArray(),
            'rats' => $ratsData,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $rats->currentPage(),
                'last_page' => $rats->lastPage(),
                'per_page' => $rats->perPage(),
                'total' => $rats->total(),
            ],
            'municipalities' => [], // TODO: Buscar do banco
            'cobradeTypes' => [], // TODO: Buscar do banco
            'years' => range(date('Y'), 2020, -1),
        ]);
    }
}

