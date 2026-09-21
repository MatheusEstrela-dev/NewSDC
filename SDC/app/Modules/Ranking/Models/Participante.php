<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\EscopoPlacar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Participante extends RankingModel
{
    protected $table = 'ranking.participantes';

    protected $fillable = [
        'periodo_id',
        'escopo',
        'entidade_id',
        'regiao',
        'tipo',
        'elegivel',
    ];

    protected function casts(): array
    {
        return [
            'periodo_id' => 'integer',
            'entidade_id' => 'integer',
            'elegivel' => 'boolean',
            'escopo' => EscopoPlacar::class,
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}

