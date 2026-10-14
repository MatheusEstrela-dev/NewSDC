<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Extrato do estoque: cada entrada ou saida, com o saldo do lote depois dela. */
class MovimentacaoEstoque extends Model
{
    public const UPDATED_AT = null;

    public const ENTRADA = 'entrada';
    public const SAIDA   = 'saida';

    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'tipo', 'produto_id', 'lote_id', 'almoxarifado_id', 'quantidade', 'saldo_apos', 'romaneio',
        'destino_tipo', 'destino_municipio_id', 'destino_descricao', 'documento', 'observacao', 'user_id',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'saldo_apos' => 'integer',
        'created_at' => 'datetime',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id')->withTrashed();
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function almoxarifado(): BelongsTo
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_id');
    }

    public function destinoMunicipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'destino_municipio_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
