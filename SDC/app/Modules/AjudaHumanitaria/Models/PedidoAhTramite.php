<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-14: log imutavel de tramitacao. Nao ha update nem delete previstos.
 */
class PedidoAhTramite extends Model
{
    protected $table = 'pedido_ah_tramites';

    protected $fillable = [
        'pedido_ah_id',
        'status_anterior',
        'status_novo',
        'observacao',
        'user_id',
    ];

    protected $casts = [
        'status_anterior' => StatusPedidoAh::class,
        'status_novo'     => StatusPedidoAh::class,
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
