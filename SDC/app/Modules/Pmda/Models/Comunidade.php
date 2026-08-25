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
        'legacy_id', 'municipio_id', 'nome', 'latitude', 'longitude',
        'trecho_pav', 'trecho_n_pav', 'pop_atendida', 'ponto_legacy_id',
        'ativo', 'created_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'trecho_pav' => 'decimal:2',
        'trecho_n_pav' => 'decimal:2',
        'pop_atendida' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
