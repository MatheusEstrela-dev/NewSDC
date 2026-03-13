<?php

declare(strict_types=1);

namespace App\Models\Rat\Relatos;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Rat\Recursos\RatRecursosEmpregado;

/**
 * Recursos utilizados em uma ocorrência RAT.
 *
 * Campos-chave (ver migração 2026_02_10_100002):
 * @property int         $id
 * @property string|null $recurso_tipo    viatura|pe|aereo|aquatico|outro
 * @property string|null $viatura_placa
 * @property string|null $viatura_tipo
 */
class RatRelatoRecurso extends RatRelato
{
    /**
     * Cascata de soft delete: ao excluir um relato de recurso,
     * os recursos empregados e componentes de guarnição também são soft-deletados.
     * Os dados permanecem no banco — apenas o deleted_at é preenchido.
     */
    protected static function booted(): void
    {
        parent::booted();
        static::deleting(function (self $recurso): void {
            $recurso->recursosEmpregados()->each->delete();
        });
    }

    protected $table = 'rat_relato_recursos';

    protected $fillable = [
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
    ];

    protected $casts = [
        'recurso_problemas' => 'boolean',
        'viatura_saida'     => 'datetime',
        'viatura_chegada'   => 'datetime',
        'viatura_km'        => 'decimal:2',
        'viatura_quantidade' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Recursos empregados (guarnições, viaturas, pessoal) neste relato. */
    public function recursosEmpregados(): HasMany
    {
        return $this->hasMany(RatRecursosEmpregado::class, 'relato_recurso_id');
    }
}
