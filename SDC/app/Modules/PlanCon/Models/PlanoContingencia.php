<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\PlanCon\Enums\SituacaoPlano;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class PlanoContingencia extends Model implements Rastreavel
{
    use TrilhaDeAcoes;

    protected $table = 'planos_contingencia';

    protected $fillable = [
        'municipio_id',
        'nome',
        'descricao',
        'situacao',
        'data_aprovacao',
        'data_validade',
        'arquivo_url',
        'observacoes',
        'created_by',
    ];

    protected $casts = [
        'situacao' => SituacaoPlano::class,
        'data_aprovacao' => 'date',
        'data_validade' => 'date',
    ];

    /**
     * Mesmo padrao de Decretacoes\Processo: o dono e quem cadastrou, registrado no
     * momento da criacao. Sem isso a coluna nunca se preenche e a trilha nao acha
     * destinatario. Nao sobrescreve valor que ja veio (import, seeder).
     */
    protected static function booted(): void
    {
        static::creating(function (self $plano): void {
            if ($plano->created_by === null && Auth::id() !== null) {
                $plano->created_by = Auth::id();
            }
        });
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function isRegular(): bool
    {
        return $this->situacao === SituacaoPlano::REGULAR;
    }

    public function isVigente(): bool
    {
        if (!$this->data_validade) {
            return true;
        }

        return $this->data_validade->isFuture();
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'plancon';
    }

    public function rotuloProtocolo(): string
    {
        $nome = trim((string) $this->nome);

        return $nome === ''
            ? 'Plano de contingencia #'.$this->getKey()
            : 'Plano de contingencia "'.$nome.'"';
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    public function urlNotificacao(): ?string
    {
        // O modulo expoe apenas o index (plancon.index): nao existe rota de um plano.
        return '/plancon';
    }

    public function campoSituacao(): ?string
    {
        return 'situacao';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->situacao instanceof SituacaoPlano ? $this->situacao->label() : null;
    }

    /**
     * Plano irregular e problema que o dono precisa ver como tal.
     */
    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->situacao === SituacaoPlano::REGULAR ? 'success' : 'warning';
    }
}
