<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use App\Modules\Estoque\Enums\CategoriaEstoque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Item do catalogo de donativos e insumos (#32). O saldo esta nos lotes. */
class Produto extends Model
{
    use SoftDeletes;

    protected $table = 'estoque_produtos';

    protected $fillable = [
        'sku', 'nome', 'categoria', 'unidade_base', 'patrimonio',
        'estoque_minimo', 'controla_validade', 'ativo',
    ];

    protected $casts = [
        'categoria'         => CategoriaEstoque::class,
        'estoque_minimo'    => 'integer',
        'controla_validade' => 'boolean',
        'ativo'             => 'boolean',
    ];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'produto_id');
    }
}
