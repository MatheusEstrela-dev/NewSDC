<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Models;

use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Regiao de Defesa Civil (REDEC) de Minas Gerais.
 *
 * Catalogo de referencia, primeiro elo da cadeia banco -> DTO -> front. Antes
 * era um enum PHP com as sedes num `match`, o que exigia deploy para corrigir
 * uma sede ou cadastrar uma regional nova.
 *
 * A chave primaria NAO e auto-incremento: o id e o proprio numero da REDEC, o
 * mesmo do legado (`cedec_municipio.redec_id`) e o que esta gravado em
 * `dec_entrada_processos.redec_id`.
 *
 * @property int $id Numero da REDEC (1..19 hoje)
 * @property string $sigla
 * @property string $sede
 * @property string|null $rpm
 * @property string $nome
 * @property bool $ativo
 */
class Redec extends Model
{
    protected $table = 'dec_redecs';

    /** O id e o numero da REDEC, atribuido na carga - nunca gerado pelo banco. */
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'sigla',
        'sede',
        'rpm',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'id'    => 'int',
        'ativo' => 'bool',
    ];

    /**
     * Toda escrita pelo Eloquent invalida o catalogo cacheado.
     *
     * Sem isso, cadastrar ou desativar uma regional so apareceria nas telas
     * quando o cache de 24h expirasse - exatamente a demora que sair do enum
     * veio eliminar. Escrita em SQL cru nao passa por aqui: nesse caso chame
     * RedecService::clearCache() ou `php artisan cache:clear`.
     */
    protected static function booted(): void
    {
        $invalida = static fn () => RedecService::clearCache();

        static::saved($invalida);
        static::deleted($invalida);
    }

    /** Apenas as regionais em uso - regional desativada sai das listas suspensas. */
    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /** Ordem natural das regionais: pelo numero, que e o proprio id. */
    public function scopeEmOrdem(Builder $query): Builder
    {
        return $query->orderBy('id');
    }
}
