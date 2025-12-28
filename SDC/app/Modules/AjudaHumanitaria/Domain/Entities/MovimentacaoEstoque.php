<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Entities;

use App\Modules\AjudaHumanitaria\Domain\ValueObjects\TipoMovimentacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Entity: Movimentação de Estoque
 *
 * Representa entrada/saída/ajuste de itens no estoque
 */
class MovimentacaoEstoque extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'estoque_id',
        'tipo_movimentacao',
        'quantidade',
        'data_movimentacao',
        'origem_destino',
        'doacao_id',
        'auxilio_id',
        'abrigo_id',
        'responsavel',
        'observacoes',
        'created_by',
    ];

    protected $casts = [
        'data_movimentacao' => 'date',
        'quantidade' => 'integer',
        'tipo_movimentacao' => TipoMovimentacao::class,
    ];

    /**
     * Relacionamento: Estoque
     */
    public function estoque(): BelongsTo
    {
        return $this->belongsTo(Estoque::class, 'estoque_id');
    }

    /**
     * Relacionamento: Auxílio (se for saída por distribuição)
     */
    public function auxilio(): BelongsTo
    {
        return $this->belongsTo(Auxilio::class, 'auxilio_id');
    }
}
