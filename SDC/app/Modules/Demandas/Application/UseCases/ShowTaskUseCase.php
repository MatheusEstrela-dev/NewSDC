<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

class ShowTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $id): ?Task
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        return $task;
    }
}
