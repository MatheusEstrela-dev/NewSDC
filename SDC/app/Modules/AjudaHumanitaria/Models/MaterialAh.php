<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RN-07: catalogo de material. A disponibilidade para pedido e configuravel
 * pelo CEDEC, em vez de lista fixa em codigo.
 */
class MaterialAh extends Model
{
    protected $table = 'materiais_ah';

    protected $fillable = [
        'nome',
        'descricao',
        'unidade_medida',
        'disponivel_para_pedido',
        'codigo_legado',
    ];

    protected $casts = [
        'disponivel_para_pedido' => 'boolean',
    ];

    public function scopeDisponiveisParaPedido(Builder $query): Builder
    {
        return $query->where('disponivel_para_pedido', true);
    }

    /**
     * Saldo do material por deposito.
     *
     * Projecao do ledger: serve para somar o que existe em estoque e para
     * saber se o material ja tem historico, nao para escrever.
     */
    public function saldos(): HasMany
    {
        return $this->hasMany(SaldoEstoqueAh::class, 'material_ah_id');
    }
}
