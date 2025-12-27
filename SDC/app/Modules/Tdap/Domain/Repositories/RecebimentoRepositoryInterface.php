<?php

namespace App\Modules\Tdap\Domain\Repositories;

use App\Modules\Tdap\Domain\Entities\Recebimento;
use App\Modules\Tdap\Domain\ValueObjects\RecebimentoStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RecebimentoRepositoryInterface
{
    /**
     * Lista recebimentos com filtros e paginação
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Encontra recebimento por ID
     */
    public function findById(int $id): ?Recebimento;

    /**
     * Encontra recebimento por número
     */
    public function findByNumero(string $numero): ?Recebimento;

    /**
     * Cria novo recebimento
     */
    public function create(array $data): Recebimento;

    /**
     * Atualiza recebimento
     */
    public function update(int $id, array $data): Recebimento;

    /**
     * Remove recebimento (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Lista recebimentos por status
     */
    public function findByStatus(RecebimentoStatus $status): Collection;

    /**
     * Lista recebimentos pendentes de conferência
     */
    public function findPendentesConferencia(): Collection;

    /**
     * Obtém estatísticas de recebimentos
     */
    public function getStatistics(array $filters = []): array;
}
