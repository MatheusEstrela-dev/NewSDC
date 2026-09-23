<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Contracts;

use App\Modules\Inventario\Models\Equipamento;
use Illuminate\Pagination\LengthAwarePaginator;

interface EquipamentoRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function statistics(): array;

    public function find(int $id): ?Equipamento;

    public function save(Equipamento $equipamento): Equipamento;
}
