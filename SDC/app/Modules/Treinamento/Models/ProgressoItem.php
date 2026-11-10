<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

class ProgressoItem extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.progresso_itens';

    protected $fillable = ['inscricao_id', 'modulo_item_id', 'status', 'posicao', 'concluido_em'];

    protected $casts = [
        'status' => StatusProgresso::class,
        'posicao' => 'integer',
        'concluido_em' => 'datetime',
    ];
}
