<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Modules\Compdec\Application\DTOs\OrgaoListDTO;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListOrgaosUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Executar listagem de órgãos
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findAll($filters, $perPage);
    }

    /**
     * Executar listagem com DTOs
     */
    public function executeAsDTO(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->execute($filters, $perPage);

        return [
            'data' => collect($paginator->items())
                ->map(fn ($orgao) => OrgaoListDTO::fromEntity($orgao)->toArray())
                ->toArray(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
