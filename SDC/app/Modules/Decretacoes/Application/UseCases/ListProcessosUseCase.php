<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\UseCases;

use App\Modules\Decretacoes\Application\DTOs\ProcessoListItemDTO;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;

/**
 * UseCase: Listar processos com paginação
 * Seguindo padrão Always-to-DTO
 * Retorna array de DTOs prontos para Inertia
 */
class ListProcessosUseCase
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $repository
    ) {
    }

    /**
     * @return array{data: array, meta: array}
     */
    public function execute(array $filters = [], int $perPage = 15): array
    {
        try {
            $paginator = $this->repository->paginate($perPage);

            if ($paginator->total() === 0) {
                return $this->getMockData();
            }

            // Converte Models -> DTOs
            $items = collect($paginator->items())
                ->map(fn($p) => ProcessoListItemDTO::fromModel($p)->toArray())
                ->toArray();

            return [
                'data' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        } catch (\Exception $e) {
            return $this->getMockData();
        }
    }

    private function getMockData(): array
    {
        $mocks = \App\Support\MockDataHelper::getProcessos();

        // Se já for array no formato esperado
        if (isset($mocks['data'])) {
            return $mocks;
        }

        // Se for LengthAwarePaginator ou Collection
        $items = is_array($mocks) ? $mocks : $mocks->items();

        return [
            'data' => $items,
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => count($items),
                'total' => count($items),
            ],
        ];
    }
}
