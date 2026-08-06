<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-17: entrega de material a um beneficiario, dentro de um item da
 * prestacao de contas.
 */
class PrestacaoContaEntrega extends Model
{
    protected $table = 'prestacao_conta_entregas';

    protected $fillable = [
        'prestacao_conta_item_id',
        'nome_beneficiario',
        'rg',
        'comunidade',
        'qtd',
        'data_entrega',
    ];

    protected $casts = [
        'qtd'          => 'integer',
        'data_entrega' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContaItem::class, 'prestacao_conta_item_id');
    }
}
