<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Plantao\Enums\StatusEscalaItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Uma vaga da escala: nesta data, neste tipo de turno, este plantonista.
 *
 * E a unidade que o plantonista ve no calendario, que recebe notificacao e da
 * qual sai o botao "Assumir turno". O Rastreavel fica AQUI e nao na Escala
 * porque o dono do aviso e o plantonista da vaga, nao quem montou o mes.
 */
class EscalaItem extends Model implements Rastreavel
{
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'plantao_escala_itens';

    protected $fillable = [
        'escala_id',
        'tipo_turno_id',
        'data',
        'plantonista_id',
        'plantonista_nome',
        'status',
        'lembrete_enviado_em',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'status' => StatusEscalaItem::class,
        'lembrete_enviado_em' => 'datetime',
    ];

    // ─── Relacoes ───────────────────────────────────────────────────────────

    public function escala(): BelongsTo
    {
        return $this->belongsTo(Escala::class, 'escala_id');
    }

    public function tipoTurno(): BelongsTo
    {
        return $this->belongsTo(TipoTurno::class, 'tipo_turno_id');
    }

    public function plantonista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'plantonista_id');
    }

    /**
     * O turno que efetivamente cumpriu esta vaga, se ja foi aberto.
     * Ausente = ainda nao assumido (ou faltou, depois que a data passa).
     */
    public function plantao(): HasOne
    {
        return $this->hasOne(Plantao::class, 'escala_item_id');
    }

    // ─── Escopos ────────────────────────────────────────────────────────────

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', StatusEscalaItem::ESCALADO->value);
    }

    public function scopeDoPlantonista(Builder $query, int $userId): Builder
    {
        return $query->where('plantonista_id', $userId);
    }

    public function scopeEntre(Builder $query, Carbon $de, Carbon $ate): Builder
    {
        return $query->whereBetween('data', [$de->toDateString(), $ate->toDateString()]);
    }

    /**
     * Vagas de escala PUBLICADA. Rascunho nao aparece para o plantonista comum
     * nem gera lembrete.
     */
    public function scopeDeEscalaPublicada(Builder $query): Builder
    {
        return $query->whereHas('escala', fn (Builder $q) => $q->publicadas());
    }

    // ─── Momento do turno ───────────────────────────────────────────────────

    /**
     * Instante em que o turno comeca. Null quando o tipo nao tem hora definida.
     */
    public function inicioEm(): ?Carbon
    {
        $minutos = $this->tipoTurno?->inicioEmMinutos();

        if ($minutos === null) {
            return null;
        }

        return $this->data->copy()->startOfDay()->addMinutes($minutos);
    }

    /**
     * Instante em que o turno termina, ja somado o dia da virada quando o tipo
     * atravessa a meia-noite.
     */
    public function fimEm(): ?Carbon
    {
        $minutos = $this->tipoTurno?->fimEmMinutos();

        if ($minutos === null) {
            return null;
        }

        return $this->data->copy()->startOfDay()->addMinutes($minutos);
    }

    /**
     * Duas vagas se sobrepoem no tempo?
     *
     * O indice unico do banco cobre apenas (data, tipo_turno_id) -- ele NAO pega
     * o mesmo plantonista em 06h-16h e 08h-20h no mesmo dia, que sao tipos
     * diferentes com intervalos que se cruzam. Esta e a regra que pega.
     */
    public function conflitaCom(self $outro): bool
    {
        $inicioA = $this->inicioEm();
        $fimA = $this->fimEm();
        $inicioB = $outro->inicioEm();
        $fimB = $outro->fimEm();

        if ($inicioA === null || $fimA === null || $inicioB === null || $fimB === null) {
            return false;
        }

        // Fronteira encostada nao e conflito: 06h-16h e 16h-02h se tocam as 16h
        // e sao exatamente a escala normal do CEDEC.
        return $inicioA->lt($fimB) && $inicioB->lt($fimA);
    }

    /**
     * Horas de descanso entre o fim deste turno e o inicio do proximo.
     * Negativo significa sobreposicao.
     */
    public function horasDeIntervaloAte(self $proximo): ?float
    {
        $fim = $this->fimEm();
        $inicio = $proximo->inicioEm();

        if ($fim === null || $inicio === null) {
            return null;
        }

        return $fim->diffInMinutes($inicio, false) / 60;
    }

    // ─── Trilha de acoes (notificacao ao plantonista) ───────────────────────

    public function moduloNotificacao(): string
    {
        return 'plantao';
    }

    public function rotuloProtocolo(): string
    {
        $data = $this->data?->format('d/m/Y');
        $turno = $this->tipoTurno?->label();

        if ($data === null) {
            return 'Escala #'.$this->getKey();
        }

        return $turno === null
            ? 'Plantao de '.$data
            : "Plantao de {$data} ({$turno})";
    }

    public function nomeCurtoNotificacao(): string
    {
        return 'Escala';
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->plantonista_id === null ? [] : [(int) $this->plantonista_id];
    }

    public function urlNotificacao(): ?string
    {
        return '/plantao/escala';
    }

    public function acaoTextoNotificacao(): string
    {
        return 'Ver escala';
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof StatusEscalaItem ? $this->status->label() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        if (!$this->status instanceof StatusEscalaItem) {
            return null;
        }

        return match ($this->status) {
            StatusEscalaItem::CUMPRIDO => 'success',
            StatusEscalaItem::FALTOU => 'warning',
            default => 'info',
        };
    }

    /**
     * `lembrete_enviado_em` e escrito pelo proprio comando de lembrete. Sem
     * ignora-lo, cada lembrete enviado geraria em seguida um card de "escala
     * atualizada" -- o aviso avisando que avisou.
     *
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return ['lembrete_enviado_em', 'updated_at'];
    }
}
