<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Application\DTOs\PlantaoListDTO;
use App\Modules\Plantao\Application\UseCases\ListPlantoesUseCase;
use App\Modules\Plantao\Domain\Repositories\PlantaoRepositoryInterface;
use App\Modules\Plantao\Domain\ValueObjects\PeriodoPlantao;
use App\Modules\Plantao\Domain\ValueObjects\StatusPlantao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantaoIndexController extends Controller
{
    public function __construct(
        private readonly ListPlantoesUseCase $listUseCase,
        private readonly PlantaoRepositoryInterface $repository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'periodo', 'search']);

        try {
            $plantoes = $this->listUseCase->execute($filters, 15);
            $statistics = $this->repository->getStatistics($filters);

            if ($plantoes->total() === 0) {
                throw new \Exception("Table is empty, using mocks for preview.");
            }

            $plantoesData = [
                'data' => PlantaoListDTO::collection($plantoes->items()),
                'pagination' => [
                    'current_page' => $plantoes->currentPage(),
                    'per_page' => $plantoes->perPage(),
                    'total' => $plantoes->total(),
                    'last_page' => $plantoes->lastPage(),
                    'from' => $plantoes->firstItem(),
                    'to' => $plantoes->lastItem(),
                ],
            ];
        } catch (\Exception $e) {
            // Fallback para mock data enquanto a tabela não existe
            $plantoesData = [
                'data' => $this->getMockData(),
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 7,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => 7,
                ],
            ];
            $statistics = [
                'total' => 1284,
                'ativos' => 8,
                'finalizados_hoje' => 24,
                'equipe_online' => 12,
            ];
        }

        return Inertia::render('Plantao/PlantaoIndex', [
            'plantoes' => $plantoesData,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusPlantao::toSelectArray(),
                'periodos' => PeriodoPlantao::toSelectArray(),
            ],
        ]);
    }

    /**
     * Mock data para visualização antes da migration
     */
    private function getMockData(): array
    {
        return [
            ['id' => 1, 'data' => '15/02/2026', 'plantonista_nome' => 'Matheus Kevin Estrela', 'avatar' => 'ME', 'periodo' => '07:00hs as 19:00hs', 'status' => 'Ativo', 'observacoes' => null],
            ['id' => 2, 'data' => '14/02/2026', 'plantonista_nome' => 'Luiz Ricardo Vaco', 'avatar' => 'LV', 'periodo' => '07:00hs as 19:00hs', 'status' => 'Finalizado', 'observacoes' => null],
            ['id' => 3, 'data' => '13/02/2026', 'plantonista_nome' => 'Deimson Queiroz', 'avatar' => 'DQ', 'periodo' => '19:00hs as 07:00hs', 'status' => 'Finalizado', 'observacoes' => null],
            ['id' => 4, 'data' => '12/02/2026', 'plantonista_nome' => 'Matheus Kevin Estrela', 'avatar' => 'ME', 'periodo' => '07:00hs as 19:00hs', 'status' => 'Finalizado', 'observacoes' => null],
            ['id' => 5, 'data' => '11/02/2026', 'plantonista_nome' => 'Luiz Ricardo Vaco', 'avatar' => 'LV', 'periodo' => '07:00hs as 19:00hs', 'status' => 'Finalizado', 'observacoes' => null],
            ['id' => 6, 'data' => '10/02/2026', 'plantonista_nome' => 'Ana Paula Gomes', 'avatar' => 'AG', 'periodo' => '19:00hs as 07:00hs', 'status' => 'Finalizado', 'observacoes' => null],
            ['id' => 7, 'data' => '09/02/2026', 'plantonista_nome' => 'Bruno Silva', 'avatar' => 'BS', 'periodo' => '07:00hs as 19:00hs', 'status' => 'Finalizado', 'observacoes' => null],
        ];
    }
}
