<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fornecedor de material, migrado de aju_fornecedores.
 *
 * cpf_cnpj fica nulo em tres dos dez registros: o legado guardava documento de
 * preenchimento (00.000.000-0000-00, repetido, e um 00.000.0 truncado), e o
 * refino trata documento invalido como desconhecido em vez de identidade.
 */
class FornecedorAh extends Model
{
    protected $table = 'ajuda_h_fornecedores';

    protected $fillable = ['nome', 'cpf_cnpj', 'municipio_id', 'endereco', 'telefone', 'codigo_legado'];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaAh::class, 'fornecedor_id');
    }
}
