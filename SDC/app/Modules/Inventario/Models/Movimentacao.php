<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    protected $table = 'inventario_ti_movimentacoes';

    protected $fillable = [
        'equipamento_id', 'registrado_por_id', 'usuario_origem_id', 'usuario_destino_id',
        'estacao_origem_id', 'estacao_destino_id', 'lote_id', 'tipo', 'status', 'quantidade',
        'data_saida', 'data_prevista_devolucao', 'data_devolucao', 'retirante_nome',
        'retirante_cpf', 'retirante_contato', 'observacao',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'data_saida' => 'datetime',
        'data_prevista_devolucao' => 'datetime',
        'data_devolucao' => 'datetime',
    ];

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'equipamento_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
