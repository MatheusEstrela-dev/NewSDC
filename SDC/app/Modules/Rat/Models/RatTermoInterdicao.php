<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Termo de interdicao de uma ocorrencia do RAT (#57).
 *
 * `rascunho` pode ser editado; `emitido` e definitivo e e o que libera a
 * finalizacao de um RAT com interdicao marcada na vistoria.
 */
class RatTermoInterdicao extends Model
{
    use HasUuids;

    public const RASCUNHO = 'rascunho';
    public const EMITIDO  = 'emitido';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'rat_termos_interdicao';

    protected $fillable = [
        'ocorrencia_id',
        'origem_termo_id',
        'tipo',
        'area_interditada',
        'fundamentacao',
        'medidas',
        'prazo_dias',
        'responsavel_nome',
        'registro_crea',
        'status',
        'emitido_por',
        'emitido_em',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'prazo_dias' => 'integer',
        'emitido_em' => 'datetime',
    ];

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_termo_id');
    }

    public function emissor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'emitido_por');
    }

    public function emitido(): bool
    {
        return $this->status === self::EMITIDO;
    }
}
