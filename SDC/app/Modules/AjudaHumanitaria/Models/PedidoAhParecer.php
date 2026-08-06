<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhParecer extends Model
{
    protected $table = 'pedido_ah_pareceres';

    protected $fillable = [
        'pedido_ah_id',
        'user_id',
        'data_parecer',
        'parecer',
        'situacao',
        'etapa',
    ];

    protected $casts = [
        'data_parecer' => 'date',
        'situacao'     => SituacaoParecer::class,
        'etapa'        => EtapaParecer::class,
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
