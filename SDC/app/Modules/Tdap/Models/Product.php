<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'tdap_products';

    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'unidade_medida',
        'quantidade_minima',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'quantidade_minima' => 'integer',
    ];

    public function recebimentos(): HasMany
    {
        return $this->hasMany(Recebimento::class, 'product_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'product_id');
    }
}
