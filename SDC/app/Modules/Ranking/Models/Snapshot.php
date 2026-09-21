<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\EscopoPlacar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Snapshot extends RegistroImutavel
{
    protected $table = 'ranking.snapshots';

    protected $fillable = [
        'periodo_id',
        'escopo',
        'revisao',
        'geracao',
        'ledger_watermark',
        'motivo',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'periodo_id' => 'integer',
            'revisao' => 'integer',
            'geracao' => 'integer',
            'ledger_watermark' => 'integer',
            'criado_em' => 'immutable_datetime',
            'escopo' => EscopoPlacar::class,
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(SnapshotItem::class, 'snapshot_id');
    }
}

