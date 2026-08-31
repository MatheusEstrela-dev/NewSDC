<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Viatura extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'plantao_viaturas';

    protected $fillable = [
        'prefixo',
        'placa',
        'marca',
        'modelo',
        'localizacao',
        'exclusiva_sobreaviso',
        'status',
        'hodometro_atual',
        'nivel_combustivel',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'localizacao' => LocalizacaoViatura::class,
        'status' => StatusViatura::class,
        'nivel_combustivel' => NivelCombustivel::class,
        'exclusiva_sobreaviso' => 'boolean',
        'ativo' => 'boolean',
        'hodometro_atual' => 'integer',
    ];

    protected static function newFactory(): \Database\Factories\Plantao\ViaturaFactory
    {
        return \Database\Factories\Plantao\ViaturaFactory::new();
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ViaturaMovimentacao::class, 'viatura_id');
    }

    /**
     * A saida ainda nao retornada. Regra de negocio garante no maximo uma.
     */
    public function movimentacaoAberta(): HasOne
    {
        return $this->hasOne(ViaturaMovimentacao::class, 'viatura_id')
            ->where('status', StatusMovimentacao::EM_TRANSITO->value);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ViaturaSnapshot::class, 'viatura_id');
    }

    public function ultimoCondutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ultimo_condutor_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
