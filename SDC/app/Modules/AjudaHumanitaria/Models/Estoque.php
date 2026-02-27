<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\CategoriaProduto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Estoque
 *
 * Representa um produto/item em estoque
 */
class Estoque extends Model
{
    use HasFactory;

    protected $table = 'estoques';

    protected $fillable = [
        'descricao',
        'categoria',
        'quantidade_atual',
        'quantidade_minima',
        'unidade_medida',
        'localizacao',
        'valor_unitario_medio',
        'ultimo_inventario',
        'observacoes',
        'created_by',
    ];

    protected $casts = [
        'quantidade_atual' => 'integer',
        'quantidade_minima' => 'integer',
        'valor_unitario_medio' => 'decimal:2',
        'ultimo_inventario' => 'date',
        'categoria' => CategoriaProduto::class,
    ];

    protected $appends = [
        'status_estoque',
    ];

    /**
     * Relacionamento: Movimentacoes
     */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'estoque_id');
    }

    /**
     * Accessor: Status do estoque
     */
    public function getStatusEstoqueAttribute(): string
    {
        if ($this->quantidade_atual === 0) {
            return 'CRITICO';
        }

        if ($this->quantidade_atual <= $this->quantidade_minima) {
            return 'BAIXO';
        }

        return 'NORMAL';
    }

    /**
     * Scope: Estoque baixo
     */
    public function scopeBaixoEstoque($query)
    {
        return $query->whereRaw('quantidade_atual <= quantidade_minima');
    }

    /**
     * Scope: Estoque critico
     */
    public function scopeCritico($query)
    {
        return $query->where('quantidade_atual', 0);
    }
}
