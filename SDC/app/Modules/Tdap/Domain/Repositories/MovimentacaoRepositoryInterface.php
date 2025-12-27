<?php

namespace App\Modules\Tdap\Domain\Repositories;

use App\Modules\Tdap\Domain\Entities\Movimentacao;
use App\Modules\Tdap\Domain\ValueObjects\MovimentacaoType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MovimentacaoRepositoryInterface
{
    /**
     * Lista movimentações com filtros e paginação
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Encontra movimentação por ID
     */
    public function findById(int $id): ?Movimentacao;

    /**
     * Cria nova movimentação
     */
    public function create(array $data): Movimentacao;

    /**
     * Lista movimentações por produto
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Lista movimentações por lote
     */
    public function findByLote(int $loteId): Collection;

    /**
     * Lista movimentações por tipo
     */
    public function findByTipo(MovimentacaoType $tipo): Collection;

    /**
     * Lista movimentações de um período
     */
    public function findByPeriodo(\DateTime $inicio, \DateTime $fim): Collection;

    /**
     * Obtém estatísticas de movimentações
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Obtém histórico de movimentações de um produto
     */
    public function getHistoricoProduct(int $productId, int $limit = 50): Collection;
}
