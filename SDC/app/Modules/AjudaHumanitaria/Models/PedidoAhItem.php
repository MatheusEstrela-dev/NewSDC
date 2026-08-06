<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhItem extends Model
{
    protected $table = 'pedido_ah_itens';

    protected $fillable = [
        'pedido_ah_id',
        'material_ah_id',
        'codigo',
        'descricao_item',
        'qtd',
        'qtd_familia_atendida',
        'tipo',
    ];

    protected $casts = [
        'qtd'                  => 'integer',
        'qtd_familia_atendida' => 'integer',
        'tipo'                 => TipoItemPedido::class,
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }
}
