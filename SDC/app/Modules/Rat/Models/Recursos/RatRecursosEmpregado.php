<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Recursos;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelato;
use App\Models\Rat\Recursos\RatRecursosComponentesGuarnicao;

/**
 * Recurso empregado em atendimento (viatura, pessoal a pé, etc.).
 *
 * Campos-chave (ver migração 2026_02_10_100003):
 * @property int         $id
 * @property int|null    $relato_recurso_id
 * @property string|null $recurso_tipo       viatura|pe
 * @property string|null $viatura_placa      Placa da viatura
 * @property string|null $viatura_tipo       Tipo da viatura
 */
class RatRecursosEmpregado extends RatRecurso
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
