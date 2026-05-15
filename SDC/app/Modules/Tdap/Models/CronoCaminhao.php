<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int     $id
 * @property int     $cronograma_id
 * @property int     $caminhao_id
 * @property ?int    $comunidade_id
 * @property float   $agua_prevista
 * @property int     $num_viagens
 * @property float   $agua_entregue
 * @property float   $vr_total
 * @property int     $ordem
 */
class CronoCaminhao extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_crono_caminhoes';

    protected $fillable = [
        'cronograma_id',
        'caminhao_id',
        'comunidade_id',
        'agua_prevista',
        'num_viagens',
        'ordem',
    ];

    protected $casts = [
        'cronograma_id' => 'integer',
        'caminhao_id'   => 'integer',
        'comunidade_id' => 'integer',
        'agua_prevista' => 'decimal:2',
        'num_viagens'   => 'integer',
        'agua_entregue' => 'decimal:2',
        'vr_total'      => 'decimal:2',
        'ordem'         => 'integer',
    ];

    public function cronograma(): BelongsTo
    {
        return $this->belongsTo(Cronograma::class, 'cronograma_id');
    }

    public function caminhao(): BelongsTo
    {
        return $this->belongsTo(Caminhao::class, 'caminhao_id');
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(CronoViagem::class, 'crono_caminhao_id')->orderByDesc('data_registro');
    }

    public function viagensValidadas(): HasMany
    {
        return $this->hasMany(CronoViagem::class, 'crono_caminhao_id')->where('validado', 1);
    }

    public function viagensPendentes(): HasMany
    {
        return $this->hasMany(CronoViagem::class, 'crono_caminhao_id')->whereNull('validado');
    }

    public function scopeDoCronograma(Builder $query, int $cronogramaId): Builder
    {
        return $query->where('cronograma_id', $cronogramaId);
    }

    public function scopeDoCaminhao(Builder $query, int $caminhaoId): Builder
    {
        return $query->where('caminhao_id', $caminhaoId);
    }

    public function getPercentualEntregueAttribute(): float
    {
        $prev = (float) $this->agua_prevista;

        return $prev > 0 ? round(((float) $this->agua_entregue / $prev) * 100, 2) : 0.0;
    }
}
