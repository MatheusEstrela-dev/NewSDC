<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    protected $table = 'tdap_movimentacoes';

    protected $fillable = [
        'product_id',
        'tipo',
        'quantidade',
        'data_movimentacao',
        'destino',
        'motivo',
        'observacoes',
        'user_id',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'data_movimentacao' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
