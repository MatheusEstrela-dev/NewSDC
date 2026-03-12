<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model da ocorrência RAT (tabela rat_ocorrencias).
 * Estrutura plana: sem UUID, sem JSON columns.
 */
class Rat extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencias';

    protected $fillable = [
        'numero_bos',
        'sequencial_ano',
        'created_by',
        'status',
        'prazo_edicao',
        'updated_by',
        'ocorrencia_origem_id',
        'historico',
    ];

    protected $casts = [
        'status'               => 'integer',
        'sequencial_ano'       => 'integer',
        'ocorrencia_origem_id' => 'integer',
        'prazo_edicao'         => 'datetime',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            1       => 'Finalizado',
            default => 'Rascunho',
        };
    }

    public function isFinalized(): bool
    {
        return $this->status === 1;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** Ocorrência de origem (BOS pai). */
    public function ocorrenciaOrigem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'ocorrencia_origem_id');
    }
}