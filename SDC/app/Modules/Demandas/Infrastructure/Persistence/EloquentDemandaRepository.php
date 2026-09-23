<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Infrastructure\Persistence;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Models\Task as Demanda;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class EloquentDemandaRepository implements DemandaRepository
{
    public function findById(int $id): ?Demanda
    {
        return Demanda::with([
            'comments.user', 
            'attachments.user', 
            'auditLogs.user', 
            'approvals.aprovador', 
            'solicitante', 
            'atribuidoPara'
        ])->find($id);
    }

    public function findByProtocolo(string $protocolo): ?Demanda
    {
        return Demanda::with(['solicitante', 'atribuidoPara'])
            ->where('protocolo', $protocolo)
            ->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Demanda::query()->with(['solicitante', 'atribuidoPara']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                  ->orWhere('protocolo', 'like', "%{$filters['search']}%")
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

        if (!empty($filters['responsavel_id'])) {
            $query->where('atribuido_para_id', $filters['responsavel_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStatistics(): array
    {
        $row = Demanda::query()
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'aberta' THEN 1 ELSE 0 END) as abertas")
            ->selectRaw("SUM(CASE WHEN status IN ('em_progresso', 'em_analise', 'aguardando_terceiros') THEN 1 ELSE 0 END) as em_andamento")
            ->selectRaw("SUM(CASE WHEN status IN ('resolvida', 'fechada') THEN 1 ELSE 0 END) as concluidas")
            ->first();

        return [
            'total' => (int) $row->total,
            'abertas' => (int) $row->abertas,
            'em_andamento' => (int) $row->em_andamento,
            'concluidas' => (int) $row->concluidas,
        ];
    }

    public function save(Demanda $demanda): Demanda
    {
        $demanda->save();
        return $demanda;
    }

    public function delete(Demanda $demanda): bool
    {
        return $demanda->delete();
    }
}
