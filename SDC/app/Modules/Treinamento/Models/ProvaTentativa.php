<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tentativa de prova de uma inscricao. So conta depois de enviada
 * (finalizada_em); a aberta e retomada de onde parou.
 *
 * `ordem` e a ordem embaralhada sorteada ao iniciar, congelada para a
 * tentativa: list<array{questao_id: int, alternativas: list<int>}>.
 * `nota` (0 a 100) e `aprovada` sao gravadas no envio e nao se recalculam.
 */
class ProvaTentativa extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.prova_tentativas';

    protected $fillable = [
        'prova_id',
        'inscricao_id',
        'numero',
        'ordem',
        'iniciada_em',
        'finalizada_em',
        'nota',
        'aprovada',
    ];

    protected $casts = [
        'prova_id' => 'integer',
        'inscricao_id' => 'integer',
        'numero' => 'integer',
        'ordem' => 'array',
        'iniciada_em' => 'datetime',
        'finalizada_em' => 'datetime',
        'nota' => 'float',
        'aprovada' => 'boolean',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(ProvaResposta::class);
    }

    public function estaAberta(): bool
    {
        return $this->finalizada_em === null;
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->whereNull('finalizada_em');
    }

    public function scopeEnviadas(Builder $query): Builder
    {
        return $query->whereNotNull('finalizada_em');
    }
}
