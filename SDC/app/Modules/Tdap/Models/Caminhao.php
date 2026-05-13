<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int     $id
 * @property int     $prestador_id
 * @property string  $placa
 * @property ?string $marca
 * @property ?string $modelo
 * @property ?string $cor
 * @property ?string $ano
 * @property float   $capacidade_m3
 * @property bool    $ativo
 * @property ?string $observacoes
 * @property-read Prestador $prestador
 */
class Caminhao extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_caminhoes';

    protected $fillable = [
        'prestador_id',
        'placa',
        'marca',
        'modelo',
        'cor',
        'ano',
        'capacidade_m3',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'prestador_id'  => 'integer',
        'capacidade_m3' => 'decimal:2',
        'ativo'         => 'boolean',
    ];

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->where('prestador_id', $prestadorId);
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(placa) LIKE ?', [$like])
              ->orWhereRaw('UPPER(modelo) LIKE ?', [$like])
              ->orWhereRaw('UPPER(marca) LIKE ?', [$like]);
        });
    }
}
