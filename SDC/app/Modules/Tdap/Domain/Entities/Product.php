<?php

namespace App\Modules\Tdap\Domain\Entities;

use App\Modules\Tdap\Domain\ValueObjects\ProductType;
use App\Modules\Tdap\Domain\ValueObjects\StorageStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_products';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'tipo',
        'eh_composto',
        'volume_unitario_m3',
        'peso_unitario_kg',
        'estoque_minimo',
        'estoque_maximo',
        'estrategia_armazenamento',
        'grupo_risco',
        'dias_alerta_validade',
        'observacoes',
    ];

    protected $casts = [
        'tipo' => ProductType::class,
        'estrategia_armazenamento' => StorageStrategy::class,
        'eh_composto' => 'boolean',
        'volume_unitario_m3' => 'decimal:4',
        'peso_unitario_kg' => 'decimal:3',
        'estoque_minimo' => 'integer',
        'estoque_maximo' => 'integer',
        'dias_alerta_validade' => 'integer',
    ];

    // Business Logic Methods

    /**
     * Verifica se o produto é perecível baseado no tipo
     */
    public function isPerecivel(): bool
    {
        return $this->tipo->isPerecivel();
    }

    /**
     * Verifica se o produto pode ser armazenado junto com outro
     * (Regra: ALIMENTO não pode ficar com QUIMICO)
     */
    public function podeCompartilharLocalCom(Product $outroProduto): bool
    {
        $incompatibilidades = [
            ['ALIMENTO', 'QUIMICO'],
            ['QUIMICO', 'ALIMENTO'],
        ];

        $par = [$this->grupo_risco, $outroProduto->grupo_risco];

        foreach ($incompatibilidades as $incompativel) {
            if ($par === $incompativel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula o espaço total ocupado por uma quantidade
     */
    public function calcularVolumeTotal(int $quantidade): float
    {
        return $this->volume_unitario_m3 * $quantidade;
    }

    /**
     * Calcula o peso total de uma quantidade
     */
    public function calcularPesoTotal(int $quantidade): float
    {
        return $this->peso_unitario_kg * $quantidade;
    }

    /**
     * Verifica se o estoque está abaixo do mínimo
     */
    public function precisaRessuprimento(int $quantidadeAtual): bool
    {
        return $quantidadeAtual <= $this->estoque_minimo;
    }

    /**
     * Verifica se excedeu o estoque máximo
     */
    public function excedeuEstoqueMaximo(int $quantidadeAtual): bool
    {
        if ($this->estoque_maximo === null) {
            return false;
        }

        return $quantidadeAtual > $this->estoque_maximo;
    }

    /**
     * Retorna a estratégia de armazenamento adequada
     */
    public function getEstrategiaArmazenamento(): StorageStrategy
    {
        // Se não foi definida, usa FEFO para perecíveis e FIFO para os demais
        if ($this->estrategia_armazenamento === null) {
            return $this->isPerecivel() ? StorageStrategy::FEFO : StorageStrategy::FIFO;
        }

        return $this->estrategia_armazenamento;
    }

    // Relationships

    public function lotes(): HasMany
    {
        return $this->hasMany(ProductLote::class, 'product_id');
    }

    public function composicao(): HasMany
    {
        return $this->hasMany(ProductComposition::class, 'product_composto_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'product_id');
    }
}
