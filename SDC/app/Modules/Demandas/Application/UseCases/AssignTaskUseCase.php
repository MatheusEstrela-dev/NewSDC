<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

class AssignTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $taskId, int $agenteId): Task
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        $task->atribuido_para_id = $agenteId;

        $this->taskRepository->save($task);

        return $task;
    }
}
