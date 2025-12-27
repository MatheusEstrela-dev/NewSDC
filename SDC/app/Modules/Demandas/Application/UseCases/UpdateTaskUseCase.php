<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(int $id, array $data): Task
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new \DomainException('Demanda não encontrada');
        }

        // Atualizar campos permitidos
        if (isset($data['titulo'])) {
            $task->titulo = $data['titulo'];
        }

        if (isset($data['descricao'])) {
            $task->descricao = $data['descricao'];
        }

        if (isset($data['categoria'])) {
            $task->categoria = $data['categoria'];
        }

        if (isset($data['subcategoria'])) {
            $task->subcategoria = $data['subcategoria'];
        }

        $this->taskRepository->save($task);

        return $task;
    }
}
