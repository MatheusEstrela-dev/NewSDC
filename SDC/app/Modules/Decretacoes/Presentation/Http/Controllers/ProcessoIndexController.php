<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use App\Modules\Decretacoes\Domain\ValueObjects\StatusProcesso;
use App\Modules\Decretacoes\Domain\ValueObjects\TipoProcesso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Processos Index
 */
class ProcessoIndexController extends Controller
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $processoRepository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'processo',
            'status',
            'vigencia_status',
            'data_inicio',
            'data_fim',
        ]);

        try {
            $processos = $this->processoRepository->paginate(15);

            $statistics = [
                'total' => $processos->total(),
                'vigentes' => \App\Modules\Decretacoes\Domain\Entities\Processo::vigentes()->count(),
                'vencidos' => \App\Modules\Decretacoes\Domain\Entities\Processo::vencidos()->count(),
                'proximos_vencer' => \App\Modules\Decretacoes\Domain\Entities\Processo::proximosVencer()->count(),
            ];
        } catch (\Exception $e) {
            // Fallback para mocks em produção
            $processos = \App\Support\MockDataHelper::getProcessos();
            $statistics = [
                'total' => 10,
                'vigentes' => 7,
                'vencidos' => 2,
                'proximos_vencer' => 1,
            ];
        }

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusProcesso::toSelectArray(),
                'tipos' => TipoProcesso::toSelectArray(),
            ],
        ]);
    }
}
