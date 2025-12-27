<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipios';

    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf',
        'regiao',
        'mesorregiao',
        'microrregiao',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'codigo_ibge' => 'string',
        'nome' => 'string',
        'uf' => 'string',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
