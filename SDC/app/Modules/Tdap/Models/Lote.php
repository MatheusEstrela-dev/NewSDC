<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int     $id
 * @property int     $ata_id
 * @property int     $municipio_id
 * @property int     $prestador_id
 * @property string  $numero
 * @property ?string $nome
 * @property float   $qtd_agua_m3
 * @property float   $valor_m3
 * @property bool    $ativo
 * @property ?string $observacoes
 */
class Lote extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_lotes';

    protected $fillable = [
        'ata_id',
        'municipio_id',
        'prestador_id',
        'numero',
        'nome',
        'qtd_agua_m3',
        'valor_m3',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'ata_id'       => 'integer',
        'municipio_id' => 'integer',
        'prestador_id' => 'integer',
        'qtd_agua_m3'  => 'decimal:2',
        'valor_m3'     => 'decimal:2',
        'ativo'        => 'boolean',
    ];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class, 'ata_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }

    public function getValorTotalAttribute(): float
    {
        return (float) $this->qtd_agua_m3 * (float) $this->valor_m3;
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDaAta(Builder $query, int $ataId): Builder
    {
        return $query->where('ata_id', $ataId);
    }

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->where('prestador_id', $prestadorId);
    }
}
