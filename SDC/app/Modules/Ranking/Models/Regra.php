<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Regra extends RegistroImutavel
{
    protected $table = 'ranking.regras';

    protected $fillable = [
        'rule_key',
        'versao',
        'modulo',
        'familia',
        'pontos_base',
        'bonus_percentual',
        'aceita_bonus',
        'habilitada',
        'motivo_desabilitada',
        'vigente_de',
        'vigente_ate',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'versao' => 'integer',
            'pontos_base' => 'integer',
            'bonus_percentual' => 'integer',
            'aceita_bonus' => 'boolean',
            'habilitada' => 'boolean',
            'vigente_de' => 'immutable_datetime',
            'vigente_ate' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
        ];
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'regra_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'regra_id');
    }
}

