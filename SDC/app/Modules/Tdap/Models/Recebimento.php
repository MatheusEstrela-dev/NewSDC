<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recebimento extends Model
{
    protected $table = 'tdap_recebimentos';

    protected $fillable = [
        'product_id',
        'quantidade',
        'data_recebimento',
        'origem',
        'numero_nota',
        'status',
        'observacoes',
        'user_id',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'data_recebimento' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
