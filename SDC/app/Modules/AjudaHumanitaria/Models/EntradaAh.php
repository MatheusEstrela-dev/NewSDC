<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entrada de material em um deposito, migrada de aju_produto.
 *
 * Apesar do nome no legado, aju_produto nao e catalogo: cada linha e um
 * recebimento, com deposito, data e nota fiscal. O catalogo e materiais_ah.
 *
 * fornecedor_id existe no schema mas fica nulo em toda a carga: aju_produto
 * nao registra fornecedor. O vinculo so passa a ser preenchido quando houver
 * entrada criada pelo sistema novo.
 */
class EntradaAh extends Model
{
    protected $table = 'ajuda_h_entradas';

    protected $fillable = [
        'deposito_id',
        'fornecedor_id',
        'fonte_recurso_id',
        'nota_fiscal',
        'recebido_em',
        'cancelado',
        'registrado_por',
        'observacao',
        'payload_legado',
        'codigo_legado',
    ];

    protected $casts = [
        'recebido_em'    => 'datetime',
        'cancelado'      => 'boolean',
        'payload_legado' => 'array',
    ];

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(FornecedorAh::class, 'fornecedor_id');
    }

    public function fonteRecurso(): BelongsTo
    {
        return $this->belongsTo(FonteRecursoAh::class, 'fonte_recurso_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(EntradaItemAh::class, 'entrada_id');
    }
}
