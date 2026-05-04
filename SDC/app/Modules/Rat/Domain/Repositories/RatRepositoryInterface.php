<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Repositories;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RatRepositoryInterface
{
    public function findById(string $id): ?object;

    public function create(array $data): object;

    public function paginate(RatFilterDTO $filters): LengthAwarePaginator;

    public function getMunicipalities(): array;

    public function delete(string $id): void;

    public function updateStatus(string $id, string $status): void;

    public function update(string $id, array $data): object;

    /**
     * Retorna o maior número de sequência do protocolo RAT para o ano dado.
     * Deve ser invocado com lockForUpdate() dentro de uma transação DB.
     */
    public function getLatestSequence(int $year): int;

    /**
     * Retorna RATs como Collection, com filtro opcional de search e limite.
     * Usado pelo RagService para enriquecimento de contexto da IA.
     * @param int $limit -1 para sem limite
     */
    public function findAll(array $filters = [], int $limit = 10): Collection;
}
