<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestacaoConta extends Model
{
    protected $table = 'prestacoes_conta';

    protected $fillable = [
        'pedido_ah_id',
        'status',
        'data_limite',
        'homologado_por',
        'homologado_em',
    ];

    protected $casts = [
        'status'        => StatusPrestacaoConta::class,
        'data_limite'   => 'date',
        'homologado_em' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PrestacaoContaItem::class, 'prestacao_conta_id');
    }

    public function homologador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homologado_por');
    }
}
