<?php

namespace App\Modules\Tdap\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa a composição de produtos (Kitting)
 * Ex: Cesta Básica composta por arroz, feijão, óleo, etc.
 */
class ProductComposition extends Model
{
    protected $table = 'tdap_product_compositions';

    protected $fillable = [
        'product_composto_id', // ID do produto final (ex: Cesta Básica)
        'product_componente_id', // ID do componente (ex: Arroz)
        'quantidade',
        'unidade_medida',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
    ];

    // Business Logic Methods

    /**
     * Calcula a quantidade total de componente necessária
     */
    public function calcularQuantidadeNecessaria(int $quantidadeKits): float
    {
        return $this->quantidade * $quantidadeKits;
    }

    // Relationships

    public function produtoComposto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_composto_id');
    }

    public function produtoComponente(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_componente_id');
    }
}
