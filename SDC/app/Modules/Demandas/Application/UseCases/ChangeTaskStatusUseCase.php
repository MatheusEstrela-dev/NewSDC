<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Demandas\Domain\ValueObjects\TaskStatus;

class ChangeTaskStatusUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $taskId, string $newStatus): Task
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        // Validar se o status é válido
        $task->status = TaskStatus::from($newStatus);

        $this->taskRepository->save($task);

        return $task;
    }
}
