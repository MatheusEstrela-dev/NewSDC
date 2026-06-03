<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int            $id
 * @property string         $cnpj
 * @property string         $nome
 * @property ?string        $representante
 * @property string         $email
 * @property ?string        $tel1
 * @property ?string        $tel2
 * @property ?string        $endereco
 * @property ?string        $bairro
 * @property ?string        $cidade
 * @property ?string        $uf
 * @property ?string        $cep
 * @property bool           $ativo
 * @property ?string        $observacoes
 */
class Prestador extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_prestadores';

    protected $fillable = [
        'cnpj',
        'nome',
        'representante',
        'email',
        'tel1',
        'tel2',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function caminhoes(): HasMany
    {
        return $this->hasMany(Caminhao::class, 'prestador_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtolower($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(nome) LIKE ?', [$like])
              ->orWhere('cnpj', 'LIKE', $like)
              ->orWhereRaw('LOWER(email) LIKE ?', [$like])
              ->orWhereRaw('LOWER(representante) LIKE ?', [$like]);
        });
    }
}
