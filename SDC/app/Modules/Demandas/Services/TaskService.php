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

    /**
     * Agregacao em 1 query (antes: 4 counts sequenciais por load do index).
     * SUM(CASE) e portavel entre PostgreSQL e MySQL.
     */
    public function getStatistics(): array
    {
        $row = Task::query()
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'aberta' THEN 1 ELSE 0 END) as abertas")
            ->selectRaw("SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento")
            ->selectRaw("SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas")
            ->first();

        return [
            'total' => (int) $row->total,
            'abertas' => (int) $row->abertas,
            'em_andamento' => (int) $row->em_andamento,
            'concluidas' => (int) $row->concluidas,
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
