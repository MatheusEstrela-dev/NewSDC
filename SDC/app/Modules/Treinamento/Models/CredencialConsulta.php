<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Trilha de quem consultou a credencial de quem (dado pessoal lido).
 */
class CredencialConsulta extends Model
{
    use UsaConexaoDeTreinamento;

    public const UPDATED_AT = null;

    protected $table = 'treinamento.credencial_consultas';

    protected $fillable = [
        'user_id_consultado',
        'consultado_por',
    ];

    protected $casts = [
        'user_id_consultado' => 'integer',
        'consultado_por' => 'integer',
    ];
}
