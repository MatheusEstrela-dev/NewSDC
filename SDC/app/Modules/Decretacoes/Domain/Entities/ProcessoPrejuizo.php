<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Prejuízos Econômicos
 */
class ProcessoPrejuizo extends Model
{
    protected $table = 'processo_prejuizos';

    protected $fillable = [
        'processo_id',
        'municipio_id',
        'tipo',
        'setor',
        'area_afetada',
        'valor',
        'observacoes',
    ];

    protected $casts = [
        'area_afetada' => 'decimal:2',
        'valor' => 'decimal:2',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class);
    }
}
