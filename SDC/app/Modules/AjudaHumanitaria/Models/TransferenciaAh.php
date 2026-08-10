<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\StatusTransferencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Movimentacao de material entre dois depositos, migrada de aju_transferencia.
 *
 * O banco garante que origem e destino sao distintos
 * (ajuda_h_transf_depositos_distintos_ck). A unica linha do legado que violava
 * isso, a transferencia 37 de BH para BH, ficou de fora da carga junto com os
 * seus 2 itens: o CHECK esta certo, o dado e que nao estava.
 */
class TransferenciaAh extends Model
{
    protected $table = 'ajuda_h_transferencias';

    protected $fillable = [
        'deposito_origem_id',
        'deposito_destino_id',
        'motorista',
        'veiculo',
        'placa',
        'saiu_em',
        'chegou_em',
        'status',
        'responsavel',
        'observacao',
        'codigo_legado',
    ];

    protected $casts = [
        'saiu_em'   => 'datetime',
        'chegou_em' => 'datetime',
        'status'    => StatusTransferencia::class,
    ];

    public function origem(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_origem_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_destino_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(TransferenciaItemAh::class, 'transferencia_id');
    }
}
