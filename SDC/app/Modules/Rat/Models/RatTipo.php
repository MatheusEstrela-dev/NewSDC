<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tipos de ocorrência RAT — tabela de referência.
 *
 * Mapeia categorias de atendimento (emergência, vistoria, busca, resgate, etc.)
 * utilizadas em nat_codigo de RatRelatoDadosGerais.
 *
 * @property int         $id
 * @property string      $codigo       Código curto (ex: 'EMG', 'VST', 'BSC')
 * @property string      $descricao    Descrição legível (ex: 'Emergência Médica')
 * @property string      $categoria    emergencia|vistoria|busca|resgate|outros
 * @property bool        $ativo        Indica se o tipo está ativo para seleção
 */
class RatTipo extends Model
{
    use SoftDeletes;

    protected $table = 'rat_tipos';

    protected $fillable = [
        'codigo',
        'descricao',
        'categoria',
        'ativo',
    ];

    protected $casts = [
        'ativo'      => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Escopos
    // -------------------------------------------------------------------------

    /** Retorna apenas tipos ativos para seleção. */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /** Filtra por categoria de ocorrência. */
    public function scopeDaCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retorna todos os tipos como array id → descricao para uso em selects.
     */
    public static function toSelectArray(): array
    {
        return self::ativos()->orderBy('descricao')->pluck('descricao', 'id')->toArray();
    }
}
