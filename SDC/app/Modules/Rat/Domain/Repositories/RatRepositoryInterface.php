<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Repositories;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;

interface RatRepositoryInterface
{
    public function findById(string $id): ?Rat;

    public function create(array $data): Rat;

    public function paginate(RatFilterDTO $filters): LengthAwarePaginator;

    public function getMunicipalities(): array;

    public function delete(string $id): void;

    public function updateStatus(string $id, string $status): void;

    public function update(string $id, array $data): Rat;

    /**
     * Retorna o maior número de sequência do protocolo RAT para o ano dado.
     * Deve ser invocado com lockForUpdate() dentro de uma transação DB.
     */
    public function getLatestSequence(int $year): int;
}
