<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

class TreinamentoProfessor extends Model
{
    use UsaConexaoDeTreinamento;

    public const UPDATED_AT = null;

    protected $table = 'treinamento.treinamento_professores';

    protected $fillable = ['treinamento_id', 'user_id'];
}
