<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\TipoPeriodo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Periodo extends RankingModel
{
    protected $table = 'ranking.periodos';

    protected $fillable = [
        'tipo',
        'chave',
        'inicia_em',
        'termina_em',
    ];

    protected function casts(): array
    {
        return [
            'inicia_em' => 'immutable_datetime',
            'termina_em' => 'immutable_datetime',
            'tipo' => TipoPeriodo::class,
        ];
    }

    public function saldos(): HasMany
    {
        return $this->hasMany(Saldo::class, 'periodo_id');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(Participante::class, 'periodo_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class, 'periodo_id');
    }
}

