<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

/**
 * Catalogo de material disponivel para pedido (RN-07).
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
 */
interface MaterialAhRepositoryInterface
{
    /**
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function disponiveisParaPedido(): array;

    public function definirDisponibilidade(int $materialId, bool $disponivel): void;
}
