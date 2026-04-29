<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Recursos;

use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Recursos\RatRecursosComponentesGuarnicao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatRecursosEmpregado extends Model
{
    protected $table = 'rat_recursos_empregados';

    protected $fillable = [
        'relato_recurso_id',
        'recurso_tipo',
        'recurso_problemas',
        'recurso_descricao',
        'viatura_tipo',
        'viatura_placa',
        'viatura_prefixo',
        'viatura_padrao',
        'viatura_orgao',
        'viatura_descricao',
        'viatura_saida',
        'viatura_chegada',
        'viatura_km',
        'viatura_local_origem',
        'viatura_local_destino',
        'viatura_quantidade',
        'viatura_capacidade',
        'viatura_condicao',
        'viatura_operador',
        'operador_masp',
        'operador_is_condutor',
        'viatura_contato',
        'created_by',
        'updated_by',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Relato de recurso ao qual pertence este emprego. */
    public function relatoRecurso(): BelongsTo
    {
        return $this->belongsTo(RatRelatoRecurso::class, 'relato_recurso_id');
    }

    /** Componentes da guarnição deste recurso. */
    public function componentesGuarnicao(): HasMany
    {
        return $this->hasMany(RatRecursosComponentesGuarnicao::class, 'recurso_empregado_id');
    }
}
