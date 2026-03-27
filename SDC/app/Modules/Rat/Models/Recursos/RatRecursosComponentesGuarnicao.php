<?php

declare(strict_types=1);

namespace App\Models\Rat\Recursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Componente da guarnição — agente/servidor que participou do atendimento.
 *
 * Campos-chave (ver migração 2026_02_10_100004):
 * @property int         $id
 * @property int|null    $recurso_empregado_id
 * @property int|null    $relato_recurso_id
 * @property string|null $nome_completo
 * @property string|null $matricula
 * @property string|null $corporacao
 * @property bool        $is_condutor
 */
class RatRecursosComponentesGuarnicao extends RatRecurso
{
    protected $table = 'rat_recursos_componentes_guarnicao';

    protected $fillable = [
        'recurso_empregado_id',
        'relato_recurso_id',
        'corporacao',
        'matricula',
        'masp',
        'nome_completo',
        'pg_cargo',
        'orgao',
        'unidade',
        'funcao',
        'is_condutor',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_condutor' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Recurso empregado ao qual este componente pertence. */
    public function recursoEmpregado(): BelongsTo
    {
        return $this->belongsTo(RatRecursosEmpregado::class, 'recurso_empregado_id');
    }
}
