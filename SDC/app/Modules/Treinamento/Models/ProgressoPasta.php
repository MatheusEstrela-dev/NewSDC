<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

class ProgressoPasta extends Model
{
    use UsaConexaoDeTreinamento;

    public $timestamps = false;

    protected $table = 'treinamento.progresso_pastas';

    protected $fillable = ['inscricao_id', 'modulo_id', 'concluida_em'];

    protected $casts = ['concluida_em' => 'datetime'];
}
