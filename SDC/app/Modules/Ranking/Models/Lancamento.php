<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Lancamento extends RegistroImutavel
{
    protected $table = 'ranking.lancamentos';

    protected $fillable = [
        'transacao_id',
        'entry_key',
        'credited_user_id',
        'orgao_id',
        'municipio_id',
        'modulo',
        'regra_id',
        'regra_versao',
        'pontos_base',
        'pontos_bonus',
        'pontos',
        'competencia_em',
        'estorno_de_id',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'transacao_id' => 'integer',
            'credited_user_id' => 'integer',
            'orgao_id' => 'integer',
            'municipio_id' => 'integer',
            'regra_id' => 'integer',
            'regra_versao' => 'integer',
            'pontos_base' => 'integer',
            'pontos_bonus' => 'integer',
            'pontos' => 'integer',
            'competencia_em' => 'immutable_datetime',
            'estorno_de_id' => 'integer',
            'criado_em' => 'immutable_datetime',
        ];
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function regra(): BelongsTo
    {
        return $this->belongsTo(Regra::class, 'regra_id');
    }

    public function estornoDe(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'estorno_de_id');
    }

    public function estornos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'estorno_de_id');
    }

    public function pedidosAjuste(): HasMany
    {
        return $this->hasMany(PedidoAjuste::class, 'lancamento_id');
    }
}

