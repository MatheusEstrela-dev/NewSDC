<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Repositories;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implementação concreta do RatRepositoryInterface.
 *
 * Responsabilidades:
 * - Consultagas ao banco de dados via Eloquent
 * - Aplicar filtros, paginação e ordenação
 * - Retornar coleções de Rat models
 */
class RatRepository implements RatRepositoryInterface
{
    /**
     * Buscar um RAT por ID (UUID).
     */
    public function findById(string $id): ?Rat
    {
        return Rat::query()
            ->where('id', $id)
            ->first();
    }

    /**
     * Listar todos os RATs com paginação e filtros.
     */
    public function paginate(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = Rat::query();

        // Filtro por protocolo/número BOS
        if ($filters->protocolo) {
            $query->where('numero_bos', 'like', "%{$filters->protocolo}%");
        }

        // Filtro por status
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        // Filtro por data de início
        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }

        // Filtro por data de fim
        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }

        // Filtro por ano
        if ($filters->ano) {
            $query->whereYear('created_at', (int)$filters->ano);
        }

        // Filtro por município
        if ($filters->municipio) {
            $query->where('municipio', $filters->municipio);
        }

        // Filtro por tipo COBRADE
        if ($filters->tipoCobrade) {
            $query->where('tipo_cobrade', $filters->tipoCobrade);
        }

        // Filtro por natureza
        if ($filters->natureza) {
            $query->where('natureza', $filters->natureza);
        }

        // Filtro por criado por
        if ($filters->criadoPor) {
            $query->where('created_by_id', $filters->criadoPor);
        }

        // Ordenação padrão
        $query->orderBy('created_at', 'desc');

        // Paginação
        return $query->paginate($filters->perPage ?? 15);
    }

    /**
     * Obter lista de municípios disponíveis.
     */
    public function getMunicipalities(): array
    {
        return Rat::query()
            ->distinct()
            ->pluck('municipio')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Criar novo RAT.
     */
    public function create(array $data): Rat
    {
        return Rat::create($data);
    }

    /**
     * Atualizar um RAT existente.
     */
    public function update(string $id, array $data): Rat
    {
        $rat = $this->findById($id);

        if (!$rat) {
            throw new \Exception("RAT com ID {$id} não encontrado");
        }

        $rat->update($data);
        return $rat;
    }

    /**
     * Deletar um RAT.
     */
    public function delete(string $id): void
    {
        $rat = $this->findById($id);

        if ($rat) {
            $rat->delete();
        }
    }

    /**
     * Contar total de RATs.
     */
    public function count(): int
    {
        return Rat::count();
    }

    /**
     * Contar RATs por status.
     */
    public function countByStatus(int $status): int
    {
        return Rat::where('status', $status)->count();
    }

    /**
     * Atualizar status de um RAT.
     */
    public function updateStatus(string $id, string $status): void
    {
        $rat = $this->findById($id);
        if (!$rat) {
            throw new \Exception("RAT com ID {$id} não encontrado");
        }
        $rat->update(['status' => $status]);
    }

    /**
     * Obter a sequência mais recente de um RAT para um determinado ano.
     */
    public function getLatestSequence(int $year): int
    {
        $latest = Rat::query()->whereYear('created_at', $year)->latest('created_at')->first();
        return $latest ? (int)substr($latest->numero_bos ?? '0', -5) + 1 : 1;
    }
}
