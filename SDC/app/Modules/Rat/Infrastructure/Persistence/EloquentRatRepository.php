<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatFilterService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implementação Eloquent do repositório de RAT.
 *
 * SRP : acesso a dados via Eloquent — sem lógica de negócio.
 * DIP : implementa RatRepositoryInterface; services dependem da abstração.
 */
class EloquentRatRepository implements RatRepositoryInterface
{
    public function __construct(
        private readonly RatFilterService $filterService,
    ) {}

    public function create(array $data): Rat
    {
        return Rat::create(array_merge([
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ], $data));
    }

    public function findById(string $id): ?Rat
    {
        return Rat::with(['creator:id,name', 'updater:id,name', 'orgaoEmissor'])->find($id);
    }

    public function paginate(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = Rat::with('creator:id,name')->orderBy('created_at', 'desc');
        $this->filterService->apply($query, $filters);

        return $query->paginate($filters->perPage);
    }

    public function getMunicipalities(): array
    {
        return Rat::whereNotNull('local')
            ->get(['local'])
            ->map(fn(Rat $rat) => $rat->local['municipio'] ?? null)
            ->filter()->unique()->sort()->values()->toArray();
    }

    public function delete(string $id): void
    {
        Rat::where('id', $id)->delete();
    }

    public function updateStatus(string $id, string $status): void
    {
        Rat::where('id', $id)->update(['status' => $status, 'updated_by' => auth()->id()]);
    }

    public function update(string $id, array $data): Rat
    {
        $rat         = Rat::findOrFail($id);
        $dadosGerais = $data['dadosGerais'] ?? null;

        $rat->update(array_filter([
            'dados_gerais' => $dadosGerais,
            'comunicacao'  => $data['comunicacao'] ?? null,
            'local'        => $data['local']        ?? null,
            'endereco'     => $data['endereco']     ?? null,
            'tem_vistoria' => isset($dadosGerais['tem_vistoria'])
                ? (bool) $dadosGerais['tem_vistoria'] : null,
            'recursos'     => $data['recursos']     ?? null,
            'envolvidos'   => $data['envolvidos']   ?? null,
            'vistoria'     => $data['vistoria']     ?? null,
            'historico'    => $data['historico']    ?? null,
            'status'       => $data['status']       ?? null,
            'updated_by'   => auth()->id(),
            // 'anexos' é gerenciado exclusivamente pelo RatAttachmentService
        ], fn($v) => !is_null($v)));

        return $rat->fresh(['creator', 'updater', 'orgaoEmissor']);
    }

    /**
     * Retorna o maior número de sequência já usado no protocolo para o ano dado.
     * Chamado com lockForUpdate() dentro de uma transação ativa.
     */
    public function getLatestSequence(int $year): int
    {
        $latest = Rat::where('protocolo', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('protocolo')
            ->value('protocolo');

        if (!$latest) {
            return 0;
        }

        return (int) substr($latest, strrpos($latest, '-') + 1);
    }
}
