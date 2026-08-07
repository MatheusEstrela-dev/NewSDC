<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo de um material em um deposito.
 *
 * Projecao, nao fonte de verdade: quem manda e o ledger
 * ajuda_h_estoque_movimentos, e esta tabela e reescrita por
 * RegistrarMovimentoEstoque dentro da mesma transacao do lancamento. Por isso
 * o modelo e usado apenas para leitura; nada aqui deve gravar saldo direto,
 * sob pena de divergir do ledger.
 *
 * A chave e composta (material_ah_id, deposito_id) e nao ha coluna id, entao
 * o incremento automatico fica desligado.
 */
class SaldoEstoqueAh extends Model
{
    protected $table = 'ajuda_h_estoque_saldos';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'saldo'         => 'decimal:3',
        'atualizado_em' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_id');
    }
}
