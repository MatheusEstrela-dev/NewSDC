<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

final class Vinculo extends RankingModel
{
    protected $table = 'ranking.vinculos';

    protected $fillable = [
        'user_id',
        'orgao_id',
        'municipio_id',
        'valido_de',
        'valido_ate',
        'evidencia',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'orgao_id' => 'integer',
            'municipio_id' => 'integer',
            'valido_de' => 'immutable_datetime',
            'valido_ate' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
        ];
    }
}

