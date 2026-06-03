<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use App\Modules\Cisterna\Enums\StatusCisterna;
use App\Modules\Cisterna\Enums\TipoCisterna;
use Database\Factories\CisternaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property int             $municipio_id
 * @property string          $codigo
 * @property string          $nome
 * @property ?string         $endereco
 * @property ?float          $latitude
 * @property ?float          $longitude
 * @property ?int            $capacidade_litros
 * @property TipoCisterna    $tipo
 * @property StatusCisterna  $status
 * @property ?\Carbon\Carbon $data_instalacao
 * @property ?string         $responsavel_nome
 * @property ?string         $responsavel_telefone
 * @property ?string         $observacoes
 * @property ?int            $legacy_id
 */
class Cisterna extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cisternas';

    protected $fillable = [
        'municipio_id',
        'codigo',
        'nome',
        'endereco',
        'latitude',
        'longitude',
        'capacidade_litros',
        'tipo',
        'status',
        'data_instalacao',
        'responsavel_nome',
        'responsavel_telefone',
        'observacoes',
        'legacy_id',
    ];

    protected $casts = [
        'tipo' => TipoCisterna::class,
        'status' => StatusCisterna::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'capacidade_litros' => 'integer',
        'data_instalacao' => 'date',
        'legacy_id' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    /* Scopes */

    public function scopeAtiva(Builder $query): Builder
    {
        return $query->where('status', StatusCisterna::ATIVA->value);
    }

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeBuscarPorTermo(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termo): void {
            $q->where('codigo', 'ilike', "%{$termo}%")
                ->orWhere('nome', 'ilike', "%{$termo}%");
        });
    }

    protected static function newFactory(): CisternaFactory
    {
        return CisternaFactory::new();
    }
}
