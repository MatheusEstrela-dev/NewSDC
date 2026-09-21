<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SnapshotItem extends RegistroImutavel
{
    protected $table = 'ranking.snapshot_itens';

    protected $fillable = [
        'snapshot_id',
        'escopo',
        'entidade_id',
        'pontos',
        'posicao',
        'faixa',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_id' => 'integer',
            'entidade_id' => 'integer',
            'pontos' => 'integer',
            'posicao' => 'integer',
            'escopo' => EscopoPlacar::class,
            'faixa' => FaixaRanking::class,
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class, 'snapshot_id');
    }
}

