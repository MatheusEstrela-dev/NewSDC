<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca um usuario como escalavel, com o posto que o relatorio usa.
 *
 * Deliberadamente separado de `users`: a base tem milhares de contas COMPDEC
 * municipais que nunca fazem plantao no Predio Alterosas, e `users` e tabela
 * transversal a todo o SDC -- nao deve ganhar coluna de um modulo so.
 */
class Plantonista extends Model
{
    protected $table = 'plantao_plantonistas';

    protected $fillable = [
        'user_id',
        'posto',
        'ativo',
        'observacao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * "Sgt Leandro". Sem posto cadastrado, devolve so o nome -- nunca uma
     * string com espaco solto na frente.
     */
    public function nomeComPosto(): string
    {
        $nome = (string) ($this->user?->name ?? '');
        $posto = trim((string) $this->posto);

        return $posto === '' ? $nome : trim($posto.' '.$nome);
    }
}
