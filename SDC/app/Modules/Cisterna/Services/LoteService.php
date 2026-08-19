<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\LoteDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LoteService
{
    public function listar(int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaLote::query()
            ->withCount('ordensServico')
            ->orderByDesc('data')
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function criar(LoteDTO $dto): CisternaLote
    {
        return CisternaLote::create($dto->toArray());
    }

    public function atualizar(CisternaLote $lote, LoteDTO $dto): CisternaLote
    {
        $lote->update($dto->toArray());

        return $lote->fresh();
    }

    /**
     * @throws ValidationException quando ha ordem de servico vinculada
     */
    public function deletar(CisternaLote $lote): bool
    {
        $ordens = $lote->ordensServico()->count();

        if ($ordens > 0) {
            throw ValidationException::withMessages([
                'lote' => "Nao e possivel excluir: {$ordens} ordem(ns) de servico vinculada(s) a este lote.",
            ]);
        }

        return (bool) $lote->delete();
    }
}
