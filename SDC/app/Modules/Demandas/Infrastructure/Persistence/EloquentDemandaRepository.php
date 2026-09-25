<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Infrastructure\Persistence;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Database\Eloquent\Builder;
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
            'atribuidoPara',
            'criadoPor',
            'assunto.categoria',
        ])->find($id);
    }

    public function findByProtocolo(string $protocolo): ?Demanda
    {
        return Demanda::with(['solicitante', 'atribuidoPara'])
            ->where('protocolo', $protocolo)
            ->first();
    }

    public function escopoVisivel(Builder $query, int $viewerId, bool $manage): Builder
    {
        if ($manage) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($viewerId): void {
            $q->where('solicitante_id', $viewerId)
                ->orWhere('atribuido_para_id', $viewerId)
                ->orWhere('criado_por_id', $viewerId);
        });
    }

    public function aplicarFiltros(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $termo = '%'.$filters['search'].'%';
            $query->where(function (Builder $q) use ($termo): void {
                $q->where('titulo', 'ilike', $termo)
                    ->orWhere('protocolo', 'ilike', $termo)
                    ->orWhere('descricao', 'ilike', $termo);
            });
        }
        if (! empty($filters['etapa'])) {
            $query->whereIn('status', array_map(
                fn (StatusDemanda $s): string => $s->value,
                EtapaDemanda::from($filters['etapa'])->status(),
            ));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['prioridade'])) {
            $valores = array_values(array_filter(
                array_map(fn (Prioridade $p): ?int => PrioridadeSimples::dePrioridade($p)->value === $filters['prioridade'] ? $p->value : null, Prioridade::cases())
            ));
            $query->whereIn('prioridade', $valores);
        }
        foreach (['assunto_id' => 'assunto_id', 'solicitante_id' => 'solicitante_id', 'responsavel_id' => 'atribuido_para_id', 'criado_por_id' => 'criado_por_id'] as $chave => $coluna) {
            if (! empty($filters[$chave])) {
                $query->where($coluna, (int) $filters[$chave]);
            }
        }
        if (! empty($filters['data_inicial'])) {
            $query->whereDate('created_at', '>=', $filters['data_inicial']);
        }
        if (! empty($filters['data_final'])) {
            $query->whereDate('created_at', '<=', $filters['data_final']);
        }

        return $query;
    }

    public function paginate(array $filters, int $perPage, int $viewerId, bool $manage, ?int $page = null): LengthAwarePaginator
    {
        $query = Demanda::query()->with(['solicitante:id,name', 'atribuidoPara:id,name', 'criadoPor:id,name', 'assunto:id,nome,categoria_id', 'assunto.categoria:id,nome']);
        $this->escopoVisivel($query, $viewerId, $manage);
        $this->aplicarFiltros($query, $filters);

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page)->withQueryString();
    }

    public function getStatistics(int $viewerId, bool $manage): array
    {
        $query = $this->escopoVisivel(Demanda::query(), $viewerId, $manage);
        $emLista = static fn (EtapaDemanda $e): string => "'".implode("','", array_map(fn (StatusDemanda $s) => $s->value, $e->status()))."'";

        $row = $query
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::ABERTO).') THEN 1 ELSE 0 END) as abertas')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::EM_ANDAMENTO).') THEN 1 ELSE 0 END) as em_andamento')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::CONCLUIDO).') THEN 1 ELSE 0 END) as concluidas')
            ->selectRaw('SUM(CASE WHEN resolvido_em >= ? THEN 1 ELSE 0 END) as resolvidas_hoje', [now()->startOfDay()])
            ->first();

        return [
            'total' => (int) $row->total,
            'abertas' => (int) $row->abertas,
            'em_andamento' => (int) $row->em_andamento,
            'concluidas' => (int) $row->concluidas,
            'resolvidas_hoje' => (int) $row->resolvidas_hoje,
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
