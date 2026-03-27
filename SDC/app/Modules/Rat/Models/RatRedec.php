<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de referência das Regiões de Defesa Civil (REDEC).
 *
 * @property int    $id
 * @property string $nome   Nome completo da REDEC
 * @property string $sigla  Sigla (ex.: 1ª REDEC)
 */
class RatRedec extends Model
{
    protected $table = 'rat_redec';

    protected $fillable = [
        'nome',
        'sigla',
    ];
}
