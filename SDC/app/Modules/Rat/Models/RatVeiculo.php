<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Veículos cadastrados para uso em ocorrências RAT.
 *
 * @property int    $id
 * @property string $placa   Placa única do veículo
 * @property string $modelo
 * @property string $marca
 * @property bool   $ativo
 */
class RatVeiculo extends Model
{
    use SoftDeletes;

    protected $table = 'rat_veiculos';

    protected $fillable = [
        'placa',
        'modelo',
        'marca',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
