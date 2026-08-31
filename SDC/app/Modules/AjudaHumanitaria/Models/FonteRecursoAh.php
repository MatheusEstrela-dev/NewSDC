<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Origem do recurso que custeou o material, migrada de aju_fonte.
 *
 * No legado a fonte chegava como texto livre em aju_produto.origem, misturada
 * com tipo de movimento. O refino so vincula o que casa com o cadastro; o
 * resto do texto ficou em ajuda_h_entradas.payload_legado.
 */
class FonteRecursoAh extends Model
{
    protected $table = 'ajuda_h_fontes_recurso';

    protected $fillable = ['nome', 'codigo_legado'];

    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaAh::class, 'fonte_recurso_id');
    }
}
