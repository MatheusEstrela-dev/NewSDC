<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Classe base para todos os relatos polimórficos de uma ocorrência RAT.
 *
 * Cada relato (Dados Gerais, Envolvidos, Recursos, Vistoria) herda dessa classe.
 */
class RatRelato extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
