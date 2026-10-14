<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Composicao de um kit (ex.: kit higiene = 2 sabonetes + 1 escova + ...). */
class Kit extends Model
{
    protected $table = 'estoque_kits';

    protected $fillable = ['codigo', 'nome', 'descricao'];

    public function itens(): HasMany
    {
        return $this->hasMany(KitItem::class, 'kit_id');
    }
}
