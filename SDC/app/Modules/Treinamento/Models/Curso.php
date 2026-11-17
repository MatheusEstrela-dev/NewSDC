<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Curso do catalogo: o que varias turmas (treinamentos) tem em comum. A regra
 * das 3 inscricoes, o indicador por curso e a consulta para edital usam este
 * vinculo, nao o titulo da turma.
 */
class Curso extends Model
{
    use SoftDeletes;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.cursos';

    protected $fillable = ['nome', 'sigla', 'descricao', 'ativo', 'created_by'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function turmas(): HasMany
    {
        return $this->hasMany(Treinamento::class, 'curso_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
