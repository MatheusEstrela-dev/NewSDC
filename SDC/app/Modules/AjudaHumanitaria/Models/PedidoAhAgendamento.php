<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusAgendamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhAgendamento extends Model
{
    protected $table = 'pedido_ah_agendamentos';

    protected $fillable = [
        'pedido_ah_id',
        'municipio_id',
        'data_retirada',
        'horario',
        'status',
        'motivo_recusa',
        'usuario_aprovacao_id',
        'data_aprovacao',
    ];

    protected $casts = [
        'data_retirada'  => 'date',
        'status'         => StatusAgendamento::class,
        'data_aprovacao' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_aprovacao_id');
    }
}
