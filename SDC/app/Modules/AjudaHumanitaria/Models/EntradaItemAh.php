<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material recebido em uma entrada, migrado de aju_produto.
 *
 * Uma linha por entrada: no legado a entrada e o item sao a mesma linha, e o
 * refino apenas separou os dois conceitos.
 *
 * qtd pode ser negativa. Sao 22 linhas de correcao manual de saldo, em que o
 * legado lancava a baixa como entrada de quantidade negativa em vez de criar
 * um tipo proprio de movimento.
 *
 * valor_total e coluna gerada pelo banco a partir de qtd e valor_unitario, e
 * por isso fica fora de $fillable.
 */
class EntradaItemAh extends Model
{
    protected $table = 'ajuda_h_entrada_itens';

    public $timestamps = false;

    protected $fillable = [
        'entrada_id',
        'material_ah_id',
        'qtd',
        'valor_unitario',
        'data_validade',
    ];

    protected $casts = [
        'qtd'            => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'valor_total'    => 'decimal:2',
        'data_validade'  => 'date',
    ];

    public function entrada(): BelongsTo
    {
        return $this->belongsTo(EntradaAh::class, 'entrada_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }
}
