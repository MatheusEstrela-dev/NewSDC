<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registro mestre de comunidade por municipio (tabela comunidades).
 * Populado pela aprovacao de uma solicitacao da CEDEC e reutilizavel em
 * qualquer PMDA do mesmo municipio.
 */
class Comunidade extends Model
{
    use SoftDeletes;

    protected $table = 'comunidades';

    protected $fillable = [
        'municipio_id', 'nome', 'latitude', 'longitude', 'ativo', 'created_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
