<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\StatusReserva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ViaturaReserva extends Model
{
    use SoftDeletes;

    protected $table = 'plantao_viatura_reservas';

    protected $fillable = [
        'viatura_id',
        'agente_id',
        'agente_nome',
        'inicio_previsto',
        'fim_previsto',
        'status',
        'destino',
        'motivo',
        'movimentacao_id',
        'checkin_em',
        'checkout_em',
        'cancelada_em',
        'cancelamento_motivo',
        'cancelada_por_id',
        'cancelada_por_nome',
    ];

    protected $casts = [
        'inicio_previsto' => 'datetime',
        'fim_previsto' => 'datetime',
        'checkin_em' => 'datetime',
        'checkout_em' => 'datetime',
        'cancelada_em' => 'datetime',
        'status' => StatusReserva::class,
    ];

    public function viatura(): BelongsTo
    {
        return $this->belongsTo(Viatura::class, 'viatura_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'agente_id');
    }

    public function movimentacao(): BelongsTo
    {
        return $this->belongsTo(ViaturaMovimentacao::class, 'movimentacao_id');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelada_por_id');
    }

    /**
     * Reservas que disputam a agenda da viatura. Base da checagem de conflito
     * e do "esta viatura esta livre neste horario".
     */
    public function scopeOcupandoAgenda(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusReserva::AGENDADA->value,
            StatusReserva::EM_USO->value,
        ]);
    }

    /**
     * Sobreposicao de janelas. Encostar nao e conflito: uma reserva que termina
     * as 14h nao briga com outra que comeca as 14h, senao a agenda do dia
     * inteiro precisaria de intervalos artificiais entre viagens.
     */
    public function scopeConflitandoCom(Builder $query, Carbon $inicio, Carbon $fim): Builder
    {
        return $query
            ->where('inicio_previsto', '<', $fim)
            ->where('fim_previsto', '>', $inicio);
    }

    /**
     * A reserva ja pode gerar check-in agora? A tolerancia adianta a abertura
     * da janela: quem reservou para as 14h consegue pegar a chave as 13h45.
     * O fim nao ganha tolerancia - passou da hora, o caminho e o command de
     * expiracao ou uma reserva nova.
     */
    public function vigenteEm(Carbon $instante, int $toleranciaMinutos = 0): bool
    {
        return $instante->greaterThanOrEqualTo($this->inicio_previsto->copy()->subMinutes($toleranciaMinutos))
            && $instante->lessThanOrEqualTo($this->fim_previsto);
    }
}
