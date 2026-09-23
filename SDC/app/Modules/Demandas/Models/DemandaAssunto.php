<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandaAssunto extends Model
{
    protected $table = 'demanda_assuntos';

    protected $fillable = ['categoria_id', 'nome', 'campos_dinamicos', 'ativo'];

    protected $casts = ['campos_dinamicos' => 'array', 'ativo' => 'boolean'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(DemandaCategoria::class, 'categoria_id');
    }
}
