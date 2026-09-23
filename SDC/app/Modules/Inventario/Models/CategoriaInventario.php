<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaInventario extends Model
{
    protected $table = 'inventario_ti_categorias';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function equipamentos(): HasMany
    {
        return $this->hasMany(Equipamento::class, 'categoria_id');
    }
}
