<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

class DeleteTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $id): void
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        $this->taskRepository->delete($id);
    }
}
