<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Saldo extends RankingModel
{
    protected $table = 'ranking.saldos';

    protected $fillable = [
        'geracao',
        'periodo_id',
        'escopo',
        'entidade_id',
        'modulo',
        'pontos',
        'faixa',
        'atualizado_em',
    ];

    protected function casts(): array
    {
        return [
            'geracao' => 'integer',
            'periodo_id' => 'integer',
            'entidade_id' => 'integer',
            'pontos' => 'integer',
            'atualizado_em' => 'immutable_datetime',
            'escopo' => EscopoPlacar::class,
            'faixa' => FaixaRanking::class,
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}

