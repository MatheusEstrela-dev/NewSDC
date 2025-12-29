<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Domain\Repositories;

use App\Modules\Inmet\Domain\Entities\EstacaoMeteorologica;
use Illuminate\Support\Collection;

interface EstacaoRepositoryInterface
{
    public function findAll(): Collection;

    public function findByCodigo(string $codigo): ?EstacaoMeteorologica;

    public function findByUf(string $uf): Collection;

    public function create(array $data): EstacaoMeteorologica;

    public function update(int $id, array $data): EstacaoMeteorologica;

    public function delete(int $id): bool;

    public function updateCoordinates(string $codigo, float $lat, float $lon): bool;
}
