<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Models\User;
use App\Modules\Demandas\Application\DTOs\TaskListDTO;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Use Case: Listar Tasks
 *
 * Aplica filtros diferentes para usuários comuns vs agentes TI
 */
class ListTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {
    }

    /**
     * Executar listagem de tasks
     *
     * @param array $filters
     * @param User $user Usuário autenticado
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
    {
        // Se não for agente TI, mostrar apenas suas próprias solicitações
        if (! $user->can('demandas.manage')) {
            return $this->repository->findSolicitadasPor($user->id, $filters, $perPage);
        }

        // Se for agente TI, pode ver todas ou apenas atribuídas a ele
        if (isset($filters['minhas_tasks']) && $filters['minhas_tasks']) {
            return $this->repository->findAtribuidasPara($user->id, $filters, $perPage);
        }

        // Agente pode ver todas as tasks
        return $this->repository->findAll($filters, $perPage);
    }

    /**
     * Converter paginação para DTOs
     */
    public function executeAsDTO(array $filters, User $user, int $perPage = 15): array
    {
        $paginator = $this->execute($filters, $user, $perPage);

        return [
            'data' => collect($paginator->items())
                ->map(fn ($task) => TaskListDTO::fromEntity($task)->toArray())
                ->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
