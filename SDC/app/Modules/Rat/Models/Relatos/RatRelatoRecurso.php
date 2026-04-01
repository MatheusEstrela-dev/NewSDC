<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Modules\Rat\Models\Recursos\RatRecursosEmpregado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Relação: Empregos dete recurso (detalhes da viatura/pessoal)
     */
    public function empregados(): HasMany
    {
        return $this->hasMany(RatRecursosEmpregado::class, 'relato_recurso_id');
    }
}
