<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

class AddCommentUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $taskId, int $autorId, string $comentario, bool $visivelSolicitante = true): void
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        $task->comments()->create([
            'comentario' => $comentario,
            'autor_id' => $autorId,
            'visivel_solicitante' => $visivelSolicitante,
        ]);
    }
}
