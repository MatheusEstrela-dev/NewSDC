<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmdaComunidade extends Model
{
    use SoftDeletes;

    protected $table = 'pmda_comunidades';

    protected $fillable = [
        'pmda_plano_id', 'comunidade_id', 'municipio_id', 'ponto_id', 'nome',
        'latitude', 'longitude', 'trecho_pav', 'trecho_n_pav', 'pop_atendida',
    ];

    protected $casts = [
        'trecho_pav'   => 'decimal:2',
        'trecho_n_pav' => 'decimal:2',
        'pop_atendida' => 'integer',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PmdaPlano::class, 'pmda_plano_id');
    }

    public function representantes(): HasMany
    {
        return $this->hasMany(PmdaRepresentante::class, 'pmda_comunidade_id');
    }
}
