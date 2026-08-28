<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Plantao\Enums\StatusEscala;
use App\Modules\Plantao\Models\Escala;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Models\Plantonista;
use App\Modules\Plantao\Models\TipoTurno;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calendario da escala.
 *
 * Serve dois publicos na mesma tela: o montador, que precisa das vagas vazias e
 * dos selects, e o plantonista comum, que so quer saber quando trabalha. A
 * diferenca e resolvida por permissao no payload -- nao por rota separada --
 * para que o link do lembrete leve todo mundo ao mesmo lugar.
 */
class EscalaIndexController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $usuario = $request->user();
        [$ano, $mes] = $this->competencia($request);

        $escala = Escala::doMes($ano, $mes)
            ->with(['publicadaPor:id,name', 'criadaPor:id,name'])
            ->first();

        $podeMontar = (bool) $usuario?->can('plantao.escala.edit');

        // Rascunho e area de trabalho do montador: quem nao monta nao ve vaga
        // que ainda pode mudar, senao o plantonista se organiza em cima de algo
        // que nao vale.
        $escalaVisivel = $escala !== null
            && ($podeMontar || ($escala->status instanceof StatusEscala && $escala->status->publicada()));

        $itens = $escalaVisivel
            ? $this->escalaService->itensNoIntervalo($escala->primeiroDia(), $escala->ultimoDia())
            : collect();

        return Inertia::render('Plantao/EscalaIndex', [
            'competencia' => [
                'ano' => $ano,
                'mes' => $mes,
                // Data ancora do calendario. ISO porque o FullCalendar espera
                // yyyy-mm-dd em initialDate.
                'inicio' => Carbon::create($ano, $mes, 1)->toDateString(),
                'rotulo' => $escala?->rotulo() ?? $this->rotuloCompetencia($ano, $mes),
            ],
            'escala' => $escala === null ? null : [
                'id' => $escala->id,
                'ano' => $escala->ano,
                'mes' => $escala->mes,
                'rotulo' => $escala->rotulo(),
                'status_valor' => $escala->status?->value,
                'status_label' => $escala->status?->label(),
                'publicada' => $escala->status instanceof StatusEscala && $escala->status->publicada(),
                'editavel' => $escala->status instanceof StatusEscala && $escala->status->editavel(),
                'publicada_em' => $escala->publicada_em?->format('d/m/Y H:i'),
                'publicada_por' => $escala->publicadaPor?->name,
                'total_vagas' => $itens->count(),
            ],
            'eventos' => $this->eventos($itens, $usuario),
            'minhasVagas' => $this->minhasVagas($itens, $usuario),
            'tiposTurno' => $this->tiposTurno(),
            'plantonistas' => $podeMontar ? $this->plantonistas() : [],
            'can' => [
                'montar' => $podeMontar,
                'criar' => (bool) $usuario?->can('plantao.escala.create'),
                'publicar' => (bool) $usuario?->can('plantao.escala.publicar'),
                'gerir_plantonistas' => (bool) $usuario?->can('plantao.plantonistas.manage'),
            ],
        ]);
    }

    /**
     * Mes pedido pela query, com o mes corrente como padrao. Valores fora de
     * faixa caem no corrente em vez de estourar: a competencia vem de navegacao
     * do calendario e nao vale 500 por um clique.
     *
     * @return array{0:int,1:int}
     */
    private function competencia(Request $request): array
    {
        $hoje = Carbon::today();

        $ano = (int) $request->integer('ano', $hoje->year);
        $mes = (int) $request->integer('mes', $hoje->month);

        if ($mes < 1 || $mes > 12 || $ano < 2020 || $ano > 2100) {
            return [$hoje->year, $hoje->month];
        }

        return [$ano, $mes];
    }

    /**
     * Vagas no formato de evento do FullCalendar.
     *
     * `end` vai com o instante real de termino, ja somada a virada de dia: sem
     * isso o turno 20h-08h apareceria terminando as 08h do proprio dia, ou
     * seja, doze horas antes de comecar.
     *
     * A cor vai CRUA no evento, nunca como classe Tailwind: o Tailwind nao
     * escaneia PHP nem valores vindos do banco, e a classe seria purgada do
     * bundle.
     *
     * @param  \Illuminate\Support\Collection<int, EscalaItem>  $itens
     * @return list<array<string,mixed>>
     */
    private function eventos($itens, ?User $usuario): array
    {
        $meuId = (int) ($usuario?->id ?? 0);

        return $itens->map(function (EscalaItem $item) use ($meuId): array {
            $inicio = $item->inicioEm();
            $fim = $item->fimEm();
            $ehMinha = (int) $item->plantonista_id === $meuId;

            return [
                'id' => (string) $item->id,
                'title' => $item->plantonista_nome,
                'start' => $inicio?->toIso8601String() ?? $item->data->toDateString(),
                'end' => $fim?->toIso8601String(),
                'allDay' => $inicio === null,
                'backgroundColor' => $item->tipoTurno?->cor ?? '#64748b',
                'borderColor' => $item->tipoTurno?->cor ?? '#64748b',
                'extendedProps' => [
                    'itemId' => $item->id,
                    'data' => $item->data->toDateString(),
                    'dataLabel' => $item->data->format('d/m/Y'),
                    'plantonistaId' => (int) $item->plantonista_id,
                    'plantonistaNome' => $item->plantonista_nome,
                    'tipoTurnoId' => (int) $item->tipo_turno_id,
                    'tipoNome' => $item->tipoTurno?->nome,
                    'tipoLabel' => $item->tipoTurno?->label(),
                    'tipoLabelCurto' => $item->tipoTurno?->labelCurto(),
                    'cor' => $item->tipoTurno?->cor ?? '#64748b',
                    'statusValor' => $item->status?->value,
                    'statusLabel' => $item->status?->label(),
                    'ehMinha' => $ehMinha,
                    'jaAssumida' => $item->plantao !== null,
                    'podeAssumir' => $ehMinha && $item->plantao === null,
                ],
            ];
        })->all();
    }

    /**
     * Os proximos turnos do usuario logado.
     *
     * Existe para o celular: no telefone o calendario vira lista, e o que o
     * plantonista abre o app para ver e "quando eu trabalho", nao o mes inteiro
     * da equipe.
     *
     * @param  \Illuminate\Support\Collection<int, EscalaItem>  $itens
     * @return list<array<string,mixed>>
     */
    private function minhasVagas($itens, ?User $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        $agora = Carbon::now();

        return $itens
            ->filter(fn (EscalaItem $item) => (int) $item->plantonista_id === (int) $usuario->id)
            ->filter(fn (EscalaItem $item) => ($item->fimEm() ?? $item->data->copy()->endOfDay())->gte($agora))
            ->take(10)
            ->map(fn (EscalaItem $item) => [
                'itemId' => $item->id,
                'data' => $item->data->toDateString(),
                'dataLabel' => $item->data->format('d/m/Y'),
                'diaSemana' => $this->diaDaSemana($item->data),
                'tipoLabel' => $item->tipoTurno?->label(),
                'tipoLabelCurto' => $item->tipoTurno?->labelCurto(),
                'cor' => $item->tipoTurno?->cor ?? '#64748b',
                'statusLabel' => $item->status?->label(),
                'jaAssumida' => $item->plantao !== null,
                'podeAssumir' => $item->plantao === null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function tiposTurno(): array
    {
        return TipoTurno::escalaveis()
            ->get()
            ->map(fn (TipoTurno $tipo) => [
                'value' => $tipo->id,
                'label' => $tipo->nome.' ('.$tipo->labelCurto().')',
                'codigo' => $tipo->codigo,
                'cor' => $tipo->cor,
                'viraDia' => $tipo->vira_dia,
            ])
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function plantonistas(): array
    {
        return Plantonista::with('user:id,name')
            ->ativos()
            ->get()
            ->map(fn (Plantonista $p) => [
                // O select manda user_id: e o que a vaga guarda, e o que evita
                // um join a mais em toda validacao.
                'value' => (int) $p->user_id,
                'label' => $p->nomeComPosto(),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function diaDaSemana(Carbon $data): string
    {
        return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'][$data->dayOfWeek] ?? '';
    }

    private function rotuloCompetencia(int $ano, int $mes): string
    {
        $meses = [
            1 => 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        return ($meses[$mes] ?? (string) $mes).'/'.$ano;
    }
}
