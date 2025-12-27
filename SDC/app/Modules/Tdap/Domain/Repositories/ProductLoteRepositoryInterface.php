<?php

namespace App\Modules\Tdap\Domain\Repositories;

use App\Modules\Tdap\Domain\Entities\ProductLote;
use Illuminate\Support\Collection;

interface ProductLoteRepositoryInterface
{
    /**
     * Encontra lote por ID
     */
    public function findById(int $id): ?ProductLote;

    /**
     * Cria novo lote
     */
    public function create(array $data): ProductLote;

    /**
     * Atualiza lote
     */
    public function update(int $id, array $data): ProductLote;

    /**
     * Lista lotes de um produto
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Lista lotes disponíveis de um produto (com estoque > 0)
     */
    public function findDisponiveisByProduct(int $productId, string $orderBy = 'data_entrada ASC'): Collection;

    /**
     * Lista lotes vencidos
     */
    public function findVencidos(): Collection;

    /**
     * Lista lotes próximos do vencimento
     */
    public function findProximosVencimento(int $diasAlerta = 30): Collection;

    /**
     * Encontra o melhor lote para saída (baseado na estratégia)
     */
    public function findMelhorLoteParaSaida(int $productId, int $quantidadeNecessaria): ?ProductLote;

    /**
     * Calcula estoque total de um produto (soma de todos os lotes)
     */
    public function calcularEstoqueTotal(int $productId): int;
}
