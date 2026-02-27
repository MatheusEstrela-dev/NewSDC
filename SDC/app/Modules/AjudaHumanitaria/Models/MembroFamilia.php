<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\Parentesco;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Membro da Familia
 *
 * Representa um membro da familia de um beneficiario
 */
class MembroFamilia extends Model
{
    use HasFactory;

    protected $table = 'membros_familia';

    protected $fillable = [
        'beneficiario_id',
        'nome',
        'cpf',
        'data_nascimento',
        'parentesco',
        'possui_deficiencia',
        'tipo_deficiencia',
        'observacoes',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'possui_deficiencia' => 'boolean',
        'parentesco' => Parentesco::class,
    ];

    protected $appends = [
        'idade',
    ];

    /**
     * Relacionamento: Beneficiario responsavel
     */
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    /**
     * Accessor: Idade do membro
     */
    public function getIdadeAttribute(): ?int
    {
        if (!$this->data_nascimento) {
            return null;
        }

        return $this->data_nascimento->age;
    }

    /**
     * Scope: Filtrar por parentesco
     */
    public function scopeParentesco($query, Parentesco|string $parentesco)
    {
        $parentescoValue = $parentesco instanceof Parentesco ? $parentesco->value : $parentesco;
        return $query->where('parentesco', $parentescoValue);
    }

    /**
     * Scope: Filtrar membros com deficiencia
     */
    public function scopeComDeficiencia($query)
    {
        return $query->where('possui_deficiencia', true);
    }

    /**
     * Scope: Filtrar criancas (< 18 anos)
     */
    public function scopeCriancas($query)
    {
        return $query->whereNotNull('data_nascimento')
                     ->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 18');
    }

    /**
     * Scope: Filtrar idosos (>= 60 anos)
     */
    public function scopeIdosos($query)
    {
        return $query->whereNotNull('data_nascimento')
                     ->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 60');
    }
}
