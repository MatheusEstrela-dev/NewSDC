<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Queries;

use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Infrastructure\Persistence\EloquentDemandaRepository;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Support\Facades\DB;

final class DemandaDashboardQuery
{
    public function __construct(private readonly EloquentDemandaRepository $repository) {}

    /** @return list<array{mes: string, abertas: int, resolvidas: int}> */
    public function serieMensal(int $viewerId, bool $manage, int $meses = 10): array
    {
        $inicio = now()->startOfMonth()->subMonths($meses - 1);
        $base = fn () => $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage);

        $abertas = $base()->where('created_at', '>=', $inicio)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')->pluck('total', 'mes');
        $resolvidas = $base()->where('resolvido_em', '>=', $inicio)
            ->selectRaw("to_char(resolvido_em, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')->pluck('total', 'mes');

        $serie = [];
        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->copy()->addMonths($i)->format('Y-m');
            $serie[] = ['mes' => $mes, 'abertas' => (int) ($abertas[$mes] ?? 0), 'resolvidas' => (int) ($resolvidas[$mes] ?? 0)];
        }

        return $serie;
    }

    /** @return list<Demanda> */
    public function recentes(int $viewerId, bool $manage, int $limite = 5): array
    {
        return $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage)
            ->with(['solicitante:id,name', 'atribuidoPara:id,name', 'criadoPor:id,name', 'assunto:id,nome,categoria_id', 'assunto.categoria:id,nome'])
            ->latest()->limit($limite)->get()->all();
    }

    /** @return list<array{assunto: string, total: int, concluidas: int}> */
    public function quantitativoPorAssunto(int $viewerId, bool $manage, ?string $de, ?string $ate): array
    {
        $concluidas = implode("','", array_map(fn (StatusDemanda $s) => $s->value, EtapaDemanda::CONCLUIDO->status()));

        return $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage)
            ->leftJoin('demanda_assuntos', 'demanda_assuntos.id', '=', 'tasks.assunto_id')
            ->when($de, fn ($q) => $q->whereDate('tasks.created_at', '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate('tasks.created_at', '<=', $ate))
            ->groupBy('demanda_assuntos.nome')
            ->orderByDesc(DB::raw('count(*)'))
            ->get([
                DB::raw("coalesce(demanda_assuntos.nome, 'Sem assunto') as assunto"),
                DB::raw('count(*) as total'),
                DB::raw("sum(case when tasks.status in ('{$concluidas}') then 1 else 0 end) as concluidas"),
            ])
            ->map(fn ($r): array => ['assunto' => (string) $r->assunto, 'total' => (int) $r->total, 'concluidas' => (int) $r->concluidas])
            ->all();
    }
}
