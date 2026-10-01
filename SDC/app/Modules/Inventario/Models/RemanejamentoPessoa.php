<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemanejamentoPessoa extends Model
{
    protected $table = 'inventario_ti_remanejamento_pessoas';

    protected $fillable = [
        'remanejamento_id', 'usuario_id', 'estacao_origem_id', 'estacao_destino_id',
        'estacao_destino_usuario_anterior_id', 'estacao_origem_ficou_vazia', 'condicao_destino',
    ];

    protected $casts = ['estacao_origem_ficou_vazia' => 'boolean'];

    public function remanejamento(): BelongsTo
    {
        return $this->belongsTo(Remanejamento::class, 'remanejamento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_origem_id');
    }

    public function estacaoDestino(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_destino_id');
    }

    public function usuarioAnteriorDoDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estacao_destino_usuario_anterior_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'remanejamento_pessoa_id');
    }
}
