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

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {
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
            $ratsResult = $this->listRatsUseCase->executeAsDTO($filters, 15);

            return Inertia::render('RatIndex', [
                'statistics' => $statistics->toArray(),
                'rats' => $ratsResult['data'],
                'pagination' => $ratsResult['pagination'],
                'filters' => $filters,
                'municipalities' => [], // TODO: Buscar do banco
                'cobradeTypes' => [], // TODO: Buscar do banco
                'years' => range(date('Y'), 2020, -1),
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar RATs. Por favor, tente novamente.');
        }
    }
}

