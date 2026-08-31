<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um lancamento do ledger de estoque.
 *
 * Fonte de verdade do saldo: ajuda_h_estoque_saldos e projecao reescrita a
 * partir daqui. Somente leitura neste model, pelo mesmo motivo do
 * SaldoEstoqueAh: quem escreve e o RegistrarMovimentoEstoque, dentro da
 * transacao do lancamento. Gravar por aqui produziria movimento sem
 * reprojecao de saldo.
 *
 * O tipo nao tem cast de enum de proposito. Ver TipoMovimentoEstoque.
 */
class MovimentoEstoqueAh extends Model
{
    protected $table = 'ajuda_h_estoque_movimentos';

    /** O ledger e append-only: a linha nasce com created_at e nunca muda. */
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'ocorrido_em'    => 'datetime',
        'created_at'     => 'datetime',
        'payload_legado' => 'array',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
