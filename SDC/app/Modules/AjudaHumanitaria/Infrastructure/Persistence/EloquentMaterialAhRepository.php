<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;

/**
 * RN-07: o catalogo de material disponivel para pedido e configuravel pelo
 * CEDEC. No legado a flag vive em aju_unidade.pedido_h e hoje marca nove
 * materiais; o port Laravel havia regredido isso para quatro itens fixos em
 * codigo, duplicados entre as telas de criacao e de edicao.
 */
final class EloquentMaterialAhRepository implements MaterialAhRepositoryInterface
{
    /**
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function disponiveisParaPedido(): array
    {
        return MaterialAh::query()
            ->where('disponivel_para_pedido', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'unidade_medida'])
            ->map(static fn (MaterialAh $material): array => [
                'id'             => (int) $material->id,
                'nome'           => (string) $material->nome,
                'unidade_medida' => (string) $material->unidade_medida,
            ])
            ->all();
    }

    public function definirDisponibilidade(int $materialId, bool $disponivel): void
    {
        MaterialAh::query()
            ->whereKey($materialId)
            ->update(['disponivel_para_pedido' => $disponivel]);
    }
}
