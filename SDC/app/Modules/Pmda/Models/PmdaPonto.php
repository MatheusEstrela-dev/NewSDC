<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ponto de captacao de agua (tabela compartilhada pip_pmda_ponto).
 * Modelo anemico local ao modulo Pmda para evitar acoplamento cross-module.
 */
class PmdaPonto extends Model
{
    use SoftDeletes;

    protected $table = 'pip_pmda_ponto';

    protected $fillable = [
        'municipio_id', 'nome', 'tipo', 'latitude', 'longitude', 'capacidade', 'ativo',
    ];

    protected $casts = [
        'tipo'       => 'integer',
        'capacidade' => 'decimal:2',
        'ativo'      => 'boolean',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
