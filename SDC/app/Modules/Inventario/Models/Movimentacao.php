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
        'estacao_origem_id', 'estacao_destino_id', 'lote_id', 'remanejamento_pessoa_id', 'tipo', 'status',
        'situacao_origem', 'quantidade', 'data_saida', 'data_prevista_devolucao', 'data_devolucao',
        'retirante_nome', 'retirante_cpf', 'retirante_contato', 'observacao',
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

    public function remanejamento(): BelongsTo
    {
        return $this->belongsTo(Remanejamento::class, 'lote_id');
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(RemanejamentoPessoa::class, 'remanejamento_pessoa_id');
    }

    public function usuarioOrigem(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_origem_id');
    }

    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_destino_id');
    }

    public function estacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_origem_id');
    }

    public function estacaoDestino(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_destino_id');
    }
}
