<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprovante de retirada de uma liberacao, migrado de aju_pagamento.
 *
 * "Pagamento" no legado nao e financeiro: e o registro de quem retirou o
 * material, com documento, placa do veiculo e responsavel. Renomeado para
 * recibo para nao induzir a leitura errada.
 */
class LiberacaoReciboAh extends Model
{
    protected $table = 'ajuda_h_liberacao_recibos';

    protected $fillable = [
        'liberacao_id',
        'pago_em',
        'n_documento',
        'n_recibo',
        'responsavel_recebimento',
        'cpf_responsavel',
        'placa_veiculo',
        'status',
        'motivo',
    ];

    protected $casts = [
        'pago_em' => 'date',
    ];

    public function liberacao(): BelongsTo
    {
        return $this->belongsTo(LiberacaoAh::class, 'liberacao_id');
    }
}
