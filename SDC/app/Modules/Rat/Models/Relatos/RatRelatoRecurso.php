<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RatRelatoRecurso - Relato de recursos empregados na ocorrência
 */
class RatRelatoRecurso extends RatRelato
{
    use HasFactory;

    protected $table = 'rat_relato_recursos';

    protected $fillable = [
        'ocorrencia_id',
        'seq',
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

    protected $casts = [
        'viatura_saida' => 'datetime',
        'viatura_chegada' => 'datetime',
        'viatura_km' => 'decimal:2',
        'recurso_problemas' => 'boolean',
        'operador_is_condutor' => 'boolean',
        'viatura_quantidade' => 'integer',
    ];

    /**
     * Relação: Componentes da Guarnição / Agentes
     */
    public function agentes(): HasMany
    {
        return $this->hasMany(\App\Modules\Rat\Models\Recursos\RatRecursosComponentesGuarnicao::class, 'relato_recurso_id');
    }

    /**
     * Relação: Ocorrência pai
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }
}
