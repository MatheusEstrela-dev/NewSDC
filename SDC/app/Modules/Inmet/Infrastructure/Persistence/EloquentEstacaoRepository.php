<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Infrastructure\Persistence;

use App\Modules\Inmet\Domain\Entities\EstacaoMeteorologica;
use App\Modules\Inmet\Domain\Repositories\EstacaoRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentEstacaoRepository implements EstacaoRepositoryInterface
{
    public function findAll(): Collection
    {
        return EstacaoMeteorologica::all();
    }

    public function findByCodigo(string $codigo): ?EstacaoMeteorologica
    {
        return EstacaoMeteorologica::where('codigo', $codigo)->first();
    }

    public function findByUf(string $uf): Collection
    {
        return EstacaoMeteorologica::porUf($uf)->get();
    }

    public function create(array $data): EstacaoMeteorologica
    {
        return EstacaoMeteorologica::create($data);
    }

    public function update(int $id, array $data): EstacaoMeteorologica
    {
        $estacao = EstacaoMeteorologica::findOrFail($id);
        $estacao->update($data);
        return $estacao->refresh();
    }

    public function delete(int $id): bool
    {
        $estacao = EstacaoMeteorologica::find($id);

        if (!$estacao) {
            return false;
        }

        return $estacao->delete();
    }

    public function updateCoordinates(string $codigo, float $lat, float $lon): bool
    {
        return EstacaoMeteorologica::where('codigo', $codigo)
            ->update(['latitude' => $lat, 'longitude' => $lon]) > 0;
    }
}
