<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PedidoAjuste extends RankingModel
{
    protected $table = 'ranking.pedidos_ajuste';

    protected $fillable = [
        'lancamento_id',
        'solicitante_user_id',
        'aprovador_user_id',
        'motivo',
        'decisao',
        'justificativa',
        'decidido_em',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'lancamento_id' => 'integer',
            'solicitante_user_id' => 'integer',
            'aprovador_user_id' => 'integer',
            'decidido_em' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
        ];
    }

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'lancamento_id');
    }
}

