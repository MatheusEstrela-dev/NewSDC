<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandaCategoria extends Model
{
    protected $table = 'demanda_categorias';

    protected $fillable = [
        'nome',
        'descricao',
        'parent_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
