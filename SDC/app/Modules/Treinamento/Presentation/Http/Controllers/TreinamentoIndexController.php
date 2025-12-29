<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Application\DTOs\TreinamentoListDTO;
use App\Modules\Treinamento\Application\UseCases\Treinamento\ListTreinamentosUseCase;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use App\Modules\Treinamento\Domain\ValueObjects\StatusTreinamento;
use App\Modules\Treinamento\Domain\ValueObjects\TipoTreinamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoIndexController extends Controller
{
    public function __construct(
        private readonly ListTreinamentosUseCase $listUseCase,
        private readonly TreinamentoRepositoryInterface $repository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'tipo', 'search']);

        $treinamentos = $this->listUseCase->execute($filters, 15);
        $statistics = $this->repository->getStatistics($filters);

        return Inertia::render('Treinamento/TreinamentoIndex', [
            'treinamentos' => [
                'data' => TreinamentoListDTO::collection($treinamentos->items()),
                'pagination' => [
                    'current_page' => $treinamentos->currentPage(),
                    'per_page' => $treinamentos->perPage(),
                    'total' => $treinamentos->total(),
                    'last_page' => $treinamentos->lastPage(),
                    'from' => $treinamentos->firstItem(),
                    'to' => $treinamentos->lastItem(),
                ],
            ],
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusTreinamento::toSelectArray(),
                'tipos' => TipoTreinamento::toSelectArray(),
            ],
        ]);
    }
}
