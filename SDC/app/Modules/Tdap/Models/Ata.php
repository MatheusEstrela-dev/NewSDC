<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property string          $numero
 * @property \Carbon\Carbon  $dt_inicio
 * @property \Carbon\Carbon  $dt_final
 * @property ?string         $historico
 * @property bool            $ativo
 * @property ?string         $observacoes
 */
class Ata extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_atas';

    protected $fillable = [
        'numero',
        'dt_inicio',
        'dt_final',
        'historico',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'dt_inicio' => 'date',
        'dt_final'  => 'date',
        'ativo'     => 'boolean',
    ];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'ata_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->whereDate('dt_inicio', '<=', now())
            ->whereDate('dt_final', '>=', now());
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(numero) LIKE ?', [$like])
              ->orWhereRaw('UPPER(historico) LIKE ?', [$like]);
        });
    }
}
