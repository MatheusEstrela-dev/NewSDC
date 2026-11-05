<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Services\RatProtocoloService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EloquentRatRepository
{
    public function __construct(private readonly RatProtocoloService $protocoloService) {}

    /**
     * Numero e INSERT na mesma transacao: o advisory lock do RatProtocoloService
     * (pg_advisory_xact_lock) so serializa se vivo ate o commit do INSERT.
     * Sem Connection guardada: tudo via facade DB, seguro no pool por coroutine.
     */
    public function create(array $data): RatOcorrencia
    {
        $userId = Auth::id();

        return DB::transaction(fn () => RatOcorrencia::create([
            'numero_bos'     => $data['numero_bos'] ?? $this->protocoloService->generate(),
            'sequencial_ano' => now()->year,
            'status'         => 0,
            'created_by'     => $userId,
            'updated_by'     => $userId,
        ]));
    }

    public function findById(string $id): ?RatOcorrencia
    {
        return RatOcorrencia::with([
            'creator',
            'updater',
            'relatosMorph.conteudo',
            'historicos',
        ])->find($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return RatOcorrencia::with(['creator'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getMunicipalities(): array
    {
        return RatRelatoDadosGerais::whereNotNull('local_municipio')
            ->distinct()
            ->pluck('local_municipio')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Escrita via MODELO, e nao `where(...)->delete()`.
     *
     * Observer do Eloquent nao dispara para escrita em massa, e a listagem de
     * protocolos depende do observer para atualizar sem F5. O `?->` mantem o
     * comportamento anterior para id inexistente: nao faz nada, sem lancar.
     */
    public function delete(string $id): void
    {
        RatOcorrencia::find($id)?->delete();
    }

    /** Idem: via modelo, para o observer ver. E a mudanca que mais interessa a listagem. */
    public function updateStatus(string $id, int $status): void
    {
        RatOcorrencia::find($id)?->update([
            'status'     => $status,
            'updated_by' => Auth::id(),
        ]);
    }

    public function update(string $id, array $data): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->update(array_merge($data, ['updated_by' => Auth::id()]));
        return $ocorrencia->fresh();
    }

    public function findAll(int $limit = 10): Collection
    {
        return RatOcorrencia::with(['creator'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
