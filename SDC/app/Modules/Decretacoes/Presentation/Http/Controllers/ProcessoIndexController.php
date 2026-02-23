<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Application\UseCases\GetDecretacoesStatisticsUseCase;
use App\Modules\Decretacoes\Application\UseCases\ListProcessosUseCase;
use App\Modules\Decretacoes\Domain\ValueObjects\StatusProcesso;
use App\Modules\Decretacoes\Domain\ValueObjects\TipoProcesso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Processos Index
 *
 * Regra de Ouro: Controller não monta dados.
 * UseCases são porteiros finais - entregam ViewModels prontos.
 */
class ProcessoIndexController extends Controller
{
    /**
     * Eficiência: UseCases injetados no método, DTOs direto no Inertia
     */
    public function __invoke(
        Request $request,
        ListProcessosUseCase $listUseCase,
        GetDecretacoesStatisticsUseCase $statsUseCase
    ): Response {
        $filters = $request->only(['search', 'processo', 'status', 'vigencia_status', 'data_inicio', 'data_fim']);

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $listUseCase->execute($filters),
            'statistics' => $statsUseCase->execute(),
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusProcesso::toSelectArray(),
                'tipos' => TipoProcesso::toSelectArray(),
            ],
        ]);
    }
}
