<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Infrastructure\Persistence;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Demandas\Domain\ValueObjects\TaskStatus;
use App\Modules\Demandas\Domain\ValueObjects\TipoTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository Implementation: Eloquent Task Repository
 *
 * Implementa operações de persistência usando Eloquent ORM
 */
class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function find(int $id): ?Task
    {
        return Task::with(['solicitante', 'atribuidoPara'])->find($id);
    }

    public function findByProtocolo(string $protocolo): ?Task
    {
        return Task::with(['solicitante', 'atribuidoPara'])
            ->where('protocolo', $protocolo)
            ->first();
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with(['solicitante', 'atribuidoPara']);

        // Aplicar filtros
        $this->applyFilters($query, $filters);

        // Ordenação padrão: mais recentes primeiro, com prioridade crítica no topo
        $query->orderByRaw('CASE WHEN prioridade = 1 THEN 0 ELSE 1 END')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(int $id, array $data): Task
    {
        $task = Task::findOrFail($id);
        $task->update($data);

        return $task->fresh();
    }

    public function delete(int $id): bool
    {
        $task = Task::findOrFail($id);

        return $task->delete();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();
        $hoje = (clone $query)->whereDate('created_at', today())->count();
        $esteMes = (clone $query)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $esteAno = (clone $query)->whereYear('created_at', now()->year)->count();

        // Por Status
        $porStatus = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Por Prioridade
        $porPrioridade = (clone $query)
            ->select('prioridade', DB::raw('count(*) as total'))
            ->groupBy('prioridade')
            ->pluck('total', 'prioridade')
            ->toArray();

        // Por Tipo
        $porTipo = (clone $query)
            ->select('tipo', DB::raw('count(*) as total'))
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        // Atrasadas
        $atrasadas = (clone $query)
            ->where(function ($q) {
                $q->where('sla_resolucao_violado', true)
                    ->orWhere('sla_primeira_resposta_violado', true);
            })
            ->count();

        // Média de tempo de resolução (em horas)
        $mediaTempoResolucao = (clone $query)
            ->whereNotNull('tempo_total_resolucao')
            ->avg('tempo_total_resolucao');

        if ($mediaTempoResolucao) {
            $mediaTempoResolucao = round($mediaTempoResolucao / 60, 2); // Converter para horas
        }

        return [
            'total' => $total,
            'hoje' => $hoje,
            'esteMes' => $esteMes,
            'esteAno' => $esteAno,
            'porStatus' => $porStatus,
            'porPrioridade' => $porPrioridade,
            'porTipo' => $porTipo,
            'atrasadas' => $atrasadas,
            'mediaTempoResolucao' => $mediaTempoResolucao,
        ];
    }

    public function countByStatus(TaskStatus $status): int
    {
        return Task::where('status', $status->value)->count();
    }

    public function countAtrasadas(): int
    {
        return Task::where(function ($q) {
            $q->where('sla_resolucao_violado', true)
                ->orWhere('sla_primeira_resposta_violado', true);
        })->count();
    }

    public function findAbertasPorTipo(TipoTask $tipo, int $limit = 10): array
    {
        return Task::where('tipo', $tipo->value)
            ->where('status', TaskStatus::ABERTA->value)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findAtribuidasPara(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with(['solicitante', 'atribuidoPara'])
            ->where('atribuido_para_id', $userId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findSolicitadasPor(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with(['solicitante', 'atribuidoPara'])
            ->where('solicitante_id', $userId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getMediaTempoResolucaoPorTipo(TipoTask $tipo): ?float
    {
        $media = Task::where('tipo', $tipo->value)
            ->whereNotNull('tempo_total_resolucao')
            ->avg('tempo_total_resolucao');

        return $media ? round($media / 60, 2) : null; // Converter para horas
    }

    /**
     * Aplicar filtros à query
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['protocolo'])) {
            $query->where('protocolo', 'like', '%' . $filters['protocolo'] . '%');
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['prioridade'])) {
            if (is_array($filters['prioridade'])) {
                $query->whereIn('prioridade', $filters['prioridade']);
            } else {
                $query->where('prioridade', $filters['prioridade']);
            }
        }

        if (! empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        if (isset($filters['solicitante_id'])) {
            $query->where('solicitante_id', $filters['solicitante_id']);
        }

        if (isset($filters['atribuido_para_id'])) {
            $query->where('atribuido_para_id', $filters['atribuido_para_id']);
        }

        if (! empty($filters['data_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['data_inicio']);
        }

        if (! empty($filters['data_fim'])) {
            $query->whereDate('created_at', '<=', $filters['data_fim']);
        }

        if (! empty($filters['atrasadas'])) {
            $query->where(function ($q) {
                $q->where('sla_resolucao_violado', true)
                    ->orWhere('sla_primeira_resposta_violado', true);
            });
        }

        // Busca textual (protocolo, título, descrição)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('protocolo', 'like', '%' . $search . '%')
                    ->orWhere('titulo', 'like', '%' . $search . '%')
                    ->orWhere('descricao', 'like', '%' . $search . '%');
            });
        }
    }
}
