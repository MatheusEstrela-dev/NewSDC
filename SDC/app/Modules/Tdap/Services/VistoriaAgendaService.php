<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Models\User;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Support\Carbon;

/**
 * Visao do vistoriador (#62): o mes num calendario so, com
 *
 * - as vistorias feitas (cor pelo parecer; "minhas" = user_id do vistoriador);
 * - o vencimento da vistoria aprovada de cada caminhao ativo -- a agenda de
 *   revistoria (validade de VigenciaVistoria, 12 meses);
 * - os turnos do proprio vistoriador na escala PUBLICADA do Plantao.
 *
 * Os eventos saem no formato do FullCalendar, consumidos pelo
 * EscalaCalendario (o unico componente que conhece a biblioteca).
 */
class VistoriaAgendaService
{
    /** Revistoria entra em alerta nos ultimos N dias de validade. */
    public const DIAS_ALERTA = 30;

    public function eventos(Carbon $de, Carbon $ate, User $user, bool $somenteMinhas): array
    {
        return [
            ...$this->vistorias($de, $ate, $user, $somenteMinhas),
            ...$this->vencimentos($de, $ate),
            ...$this->plantoes($de, $ate, $user),
        ];
    }

    /**
     * Caminhoes ativos com a vistoria vencida ou a vencer em DIAS_ALERTA dias,
     * mais urgente primeiro. Caminhao nunca vistoriado tambem entra: e o
     * trabalho mais atrasado de todos.
     */
    public function revistorias(?Carbon $hoje = null): array
    {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();
        $limite = $hoje->copy()->addDays(self::DIAS_ALERTA);

        return Caminhao::query()->ativo()
            ->with(['ultimaVistoriaAprovada', 'prestador:id,nome'])
            ->get(['id', 'placa', 'marca', 'modelo', 'prestador_id'])
            ->map(function (Caminhao $c) use ($hoje): array {
                $validoAte = VigenciaVistoria::validoAte($c->ultimaVistoriaAprovada?->data);

                return [
                    'caminhao'    => $this->caminhao($c),
                    'prestador'   => $c->prestador?->nome,
                    'valido_ate'  => $validoAte?->toDateString(),
                    'dias'        => $validoAte ? (int) $hoje->diffInDays($validoAte, false) : null,
                ];
            })
            ->filter(fn (array $r) => $r['valido_ate'] === null || Carbon::parse($r['valido_ate'])->lessThanOrEqualTo($limite))
            ->sortBy(fn (array $r) => $r['dias'] ?? PHP_INT_MIN)
            ->values()
            ->all();
    }

    private function vistorias(Carbon $de, Carbon $ate, User $user, bool $somenteMinhas): array
    {
        return Vistoria::query()
            ->whereBetween('data', [$de->toDateString(), $ate->toDateString()])
            ->when($somenteMinhas, fn ($q) => $q->where('user_id', $user->id))
            ->with(['caminhao:id,placa,marca,modelo', 'user:id,name'])
            ->get(['id', 'placa_id', 'data', 'parecer', 'user_id'])
            ->map(function (Vistoria $v) use ($user): array {
                $aprovada = $v->parecer === ParecerVistoria::Aprovada;
                $placa = $v->caminhao?->placa ?? 'sem placa';

                return [
                    'id'     => "vistoria-{$v->id}",
                    'title'  => "Vistoria {$placa}" . ($v->parecer ? ' - ' . $v->parecer->label() : ''),
                    'start'  => $v->data?->toDateString(),
                    'allDay' => true,
                    'color'  => $v->parecer === null ? '#6b7280' : ($aprovada ? '#16a34a' : '#dc2626'),
                    'extendedProps' => [
                        'tipo'        => 'vistoria',
                        'vistoria_id' => $v->id,
                        'vistoriador' => $v->user?->name,
                        'ehMinha'     => $v->user_id === $user->id,
                        'caminhao'    => $v->caminhao ? $this->caminhao($v->caminhao) : null,
                    ],
                ];
            })
            ->all();
    }

    private function vencimentos(Carbon $de, Carbon $ate): array
    {
        $hoje = Carbon::today();

        return Caminhao::query()->ativo()
            ->with('ultimaVistoriaAprovada')
            ->get(['id', 'placa', 'marca', 'modelo'])
            ->map(function (Caminhao $c) use ($de, $ate, $hoje): ?array {
                $validoAte = VigenciaVistoria::validoAte($c->ultimaVistoriaAprovada?->data);
                if ($validoAte === null || $validoAte->lt($de->copy()->startOfDay()) || $validoAte->gt($ate->copy()->endOfDay())) {
                    return null;
                }

                return [
                    'id'     => "vencimento-{$c->id}",
                    'title'  => "Vence vistoria {$c->placa}",
                    'start'  => $validoAte->toDateString(),
                    'allDay' => true,
                    'color'  => $validoAte->lt($hoje) ? '#991b1b' : '#d97706',
                    'extendedProps' => [
                        'tipo'     => 'vencimento',
                        'caminhao' => $this->caminhao($c),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function plantoes(Carbon $de, Carbon $ate, User $user): array
    {
        return EscalaItem::query()
            ->doPlantonista((int) $user->id)
            ->deEscalaPublicada()
            ->entre($de, $ate)
            ->with('tipoTurno')
            ->get()
            ->map(function (EscalaItem $item): array {
                $inicio = $item->inicioEm();
                $fim = $item->fimEm();

                return [
                    'id'     => "plantao-{$item->id}",
                    'title'  => 'Plantao ' . ($item->tipoTurno?->labelCurto() ?? ''),
                    'start'  => $inicio?->toIso8601String() ?? $item->data->toDateString(),
                    'end'    => $fim?->toIso8601String(),
                    'allDay' => $inicio === null,
                    'color'  => '#2563eb',
                    'extendedProps' => ['tipo' => 'plantao', 'ehMinha' => true],
                ];
            })
            ->all();
    }

    private function caminhao(Caminhao $c): array
    {
        return ['id' => $c->id, 'placa' => $c->placa, 'marca' => $c->marca, 'modelo' => $c->modelo];
    }
}
