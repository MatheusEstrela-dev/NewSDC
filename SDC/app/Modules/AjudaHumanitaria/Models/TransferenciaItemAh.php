<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material que segue em uma transferencia, migrado de aju_item_transf.
 *
 * O status do item nao ganha enum de proposito. No dado migrado ele assume 0
 * em 171 itens e 1 em 19, ambos dentro de transferencias concluidas, e o
 * legado nao registra em lugar algum o que separa um do outro. Rotular seria
 * inventar significado; o valor fica cru ate alguem que opera o modulo
 * esclarecer.
 */
class TransferenciaItemAh extends Model
{
    protected $table = 'ajuda_h_transferencia_itens';

    public $timestamps = false;

    protected $fillable = [
        'transferencia_id',
        'material_ah_id',
        'qtd',
        'status',
    ];

    protected $casts = [
        'qtd' => 'decimal:3',
    ];

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(TransferenciaAh::class, 'transferencia_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }
}
