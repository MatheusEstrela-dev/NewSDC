<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item de uma liberacao, migrado de aju_item.
 *
 * A tabela existe e esta vazia, e nao por falha do refino: aju_item nao tem
 * uma linha na base de producao, entao as 3.582 liberacoes migradas nao
 * registram material. Se o item da liberacao existe em algum lugar do legado,
 * e em estrutura que o dump nao cobre.
 */
class LiberacaoItemAh extends Model
{
    protected $table = 'ajuda_h_liberacao_itens';

    public $timestamps = false;

    protected $fillable = [
        'liberacao_id',
        'material_ah_id',
        'qtd',
        'status',
        'codigo_legado',
    ];

    protected $casts = [
        'qtd' => 'decimal:3',
    ];

    public function liberacao(): BelongsTo
    {
        return $this->belongsTo(LiberacaoAh::class, 'liberacao_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }
}
