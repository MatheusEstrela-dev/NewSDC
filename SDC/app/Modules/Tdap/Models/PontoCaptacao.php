<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ponto de captacao (PMDA) - origem da agua de um cronograma TDAP.
 *
 * @property int     $id
 * @property int     $municipio_id
 * @property string  $nome
 * @property int     $tipo
 * @property ?string $latitude
 * @property ?string $longitude
 * @property float   $capacidade
 * @property bool    $ativo
 */
class PontoCaptacao extends Model
{
    use SoftDeletes;

    protected $table = 'pip_pmda_ponto';

    protected $fillable = [
        'municipio_id',
        'nome',
        'tipo',
        'latitude',
        'longitude',
        'capacidade',
        'ativo',
    ];

    protected $casts = [
        'municipio_id' => 'integer',
        'tipo'         => 'integer',
        'capacidade'   => 'decimal:2',
        'ativo'        => 'boolean',
    ];

    protected $appends = ['tipo_nome'];

    /** @var array<int, string> */
    public const TIPOS = [
        1 => 'COPASA',
        2 => 'COPANOR',
        3 => 'BARRAGEM',
        4 => 'SAAE/DMAE',
        5 => 'POCO ARTESIANO PUBLICO',
        6 => 'POCO ARTESIANO PARTICULAR',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function getTipoNomeAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? 'OUTRO';
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }
}
