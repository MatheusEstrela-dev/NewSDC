<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\StatusEntrega;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

class ProjetoEntrega extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.projeto_entregas';

    protected $fillable = [
        'inscricao_id',
        'modulo_item_id',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_mime',
        'arquivo_tamanho',
        'comentario',
        'status',
        'parecer',
        'corrigido_por',
        'corrigido_em',
    ];

    protected $casts = [
        'status' => StatusEntrega::class,
        'arquivo_tamanho' => 'integer',
        'corrigido_em' => 'datetime',
    ];
}
