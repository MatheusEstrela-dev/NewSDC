<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Models\Task;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                  ->orWhere('descricao', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['prioridade'])) {
            $query->where('prioridade', $filters['prioridade']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['comments', 'attachments', 'auditLogs', 'approvals'])->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $task = Task::find($id);
        if (!$task) {
            return false;
        }
        return $task->update($data);
    }

    public function delete(int $id): bool
    {
        $task = Task::find($id);
        if (!$task) {
            return false;
        }
        return $task->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Task::count(),
            'abertas' => Task::where('status', 'aberta')->count(),
            'em_andamento' => Task::where('status', 'em_andamento')->count(),
            'concluidas' => Task::where('status', 'concluida')->count(),
        ];
    }

    public function assign(int $taskId, int $userId): bool
    {
        $task = Task::find($taskId);
        if (!$task) {
            return false;
        }
        return $task->update(['assigned_to' => $userId]);
    }

    public function changeStatus(int $taskId, string $status): bool
    {
        $task = Task::find($taskId);
        if (!$task) {
            return false;
        }
        return $task->update(['status' => $status]);
    }
}
