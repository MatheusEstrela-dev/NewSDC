<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pasta da arvore do workspace. Uma categoria pode ter subcategorias. */
class NotaTecnicaCategoria extends Model
{
    protected $table = 'nota_tecnica_categorias';

    protected $fillable = [
        'parent_id',
        'nome',
        'slug',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function filhas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordem')->orderBy('nome');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(NotaTecnica::class, 'categoria_id');
    }
}
