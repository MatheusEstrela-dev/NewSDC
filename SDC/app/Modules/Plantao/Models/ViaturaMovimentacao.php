<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViaturaMovimentacao extends Model
{
    use SoftDeletes;

    protected $table = 'plantao_viatura_movimentacoes';

    protected $fillable = [
        'viatura_id',
        'plantao_id',
        'condutor_id',
        'condutor_nome',
        'saida_em',
        'saida_hodometro',
        'saida_combustivel',
        'destino',
        'motivo',
        'retorno_em',
        'retorno_hodometro',
        'retorno_combustivel',
        'alteracoes',
        'status',
    ];

    protected $casts = [
        'saida_em' => 'datetime',
        'retorno_em' => 'datetime',
        'saida_hodometro' => 'integer',
        'retorno_hodometro' => 'integer',
        'saida_combustivel' => NivelCombustivel::class,
        'retorno_combustivel' => NivelCombustivel::class,
        'status' => StatusMovimentacao::class,
    ];

    public function viatura(): BelongsTo
    {
        return $this->belongsTo(Viatura::class, 'viatura_id');
    }

    public function plantao(): BelongsTo
    {
        return $this->belongsTo(Plantao::class, 'plantao_id');
    }

    public function condutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'condutor_id');
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->where('status', StatusMovimentacao::EM_TRANSITO->value);
    }
}
