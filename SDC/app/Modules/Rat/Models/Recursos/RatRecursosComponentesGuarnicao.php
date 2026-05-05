<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Recursos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Componente da guarnição — agente/servidor que participou do atendimento.
 */
class RatRecursosComponentesGuarnicao extends Model
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

    /** Relato de recurso ao qual este componente pertence. */
    public function relatoRecurso(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class, 'relato_recurso_id');
    }
}
