<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use App\Modules\Ranking\Enums\DecisaoPontuacao;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Transacao extends RankingModel
{
    protected $table = 'ranking.transacoes';

    protected $fillable = [
        'event_id',
        'event_name',
        'chave_canonica',
        'familia',
        'regra_id',
        'decisao',
        'motivo',
        'actor_user_id',
        'credited_user_id',
        'validador_user_id',
        'orgao_id',
        'municipio_id',
        'ocorrido_em',
        'competencia_em',
        'contexto',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            // uuid na coluna, string em PHP. Cast para integer devolvia 0 em
            // toda leitura e destruiria a barreira tecnica de idempotencia.
            'event_id' => 'string',
            'regra_id' => 'integer',
            'actor_user_id' => 'integer',
            'credited_user_id' => 'integer',
            'validador_user_id' => 'integer',
            'orgao_id' => 'integer',
            'municipio_id' => 'integer',
            'ocorrido_em' => 'immutable_datetime',
            'competencia_em' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
            'decisao' => DecisaoPontuacao::class,
            'contexto' => 'array',
        ];
    }

    public function regra(): BelongsTo
    {
        return $this->belongsTo(Regra::class, 'regra_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'transacao_id');
    }
}

