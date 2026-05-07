<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Modules\Compdec\Enums\FuncaoEquipe;
use Database\Factories\CompdecEquipeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                $id
 * @property int                $orgao_id
 * @property string             $nome
 * @property FuncaoEquipe       $funcao
 * @property ?string            $telefone
 * @property ?string            $celular
 * @property ?string            $email
 * @property ?string            $cpf
 * @property bool               $ativo
 * @property int                $ordem
 * @property ?string            $observacoes
 * @property ?int               $legacy_id
 */
class CompdecEquipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'compdec_equipes';

    protected $fillable = [
        'orgao_id',
        'nome',
        'funcao',
        'telefone',
        'celular',
        'email',
        'cpf',
        'ativo',
        'ordem',
        'observacoes',
        'legacy_id',
    ];

    protected $casts = [
        'funcao' => FuncaoEquipe::class,
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_id');
    }

    /* Scopes */

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeCoordenador(Builder $query): Builder
    {
        return $query->where('funcao', FuncaoEquipe::COORDENADOR->value);
    }

    public function scopeDoOrgao(Builder $query, int $orgaoId): Builder
    {
        return $query->where('orgao_id', $orgaoId);
    }

    public function scopeContaEfetivo(Builder $query): Builder
    {
        return $query->whereIn('funcao', [
            FuncaoEquipe::COORDENADOR->value,
            FuncaoEquipe::AGENTE->value,
            FuncaoEquipe::TECNICO->value,
        ])->where('ativo', true);
    }

    /* Helpers */

    public function isCoordenador(): bool
    {
        return $this->funcao === FuncaoEquipe::COORDENADOR;
    }

    protected static function newFactory(): CompdecEquipeFactory
    {
        return CompdecEquipeFactory::new();
    }
}
