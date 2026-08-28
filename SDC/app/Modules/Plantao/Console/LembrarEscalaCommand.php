<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Console;

use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Plantao\Models\EscalaItem;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Avisa o plantonista de que o turno dele esta proximo.
 *
 * Roda a cada 15 minutos e varre a janela [agora, agora + N minutos], com N em
 * config('plantao.escala.lembrete_minutos_antes').
 *
 * IDEMPOTENCIA. Este e o primeiro comando agendado do sistema que despacha
 * notificacao -- os outros so registram log -- e o modo de falhar dele e
 * reenviar. Tres coisas garantem um aviso por vaga:
 *
 * 1. so itens com lembrete_enviado_em nulo entram;
 * 2. a marca e gravada por UPDATE condicional (... WHERE lembrete_enviado_em IS
 *    NULL), e o job so e despachado se a linha foi de fato afetada. Duas
 *    execucoes simultaneas -- scheduler sobreposto, dois workers -- disputam a
 *    mesma linha e apenas uma ganha;
 * 3. a marca e gravada ANTES do dispatch. Perder um aviso por falha de fila e
 *    recuperavel (o job tem 3 tentativas e DLQ); mandar o mesmo aviso varias
 *    vezes ensina o usuario a ignorar o sino.
 *
 * O UPDATE direto no query builder tambem nao dispara os eventos do Eloquent,
 * entao a trilha de acoes nao transforma "marquei que avisei" em mais um card.
 */
class LembrarEscalaCommand extends Command
{
    protected $signature = 'plantao:lembrar-escala
        {--minutos= : Sobrescreve a janela de antecedencia, em minutos}
        {--dry-run : Lista o que seria enviado sem enviar nem marcar}';

    protected $description = 'Avisa plantonistas cujo turno escalado esta proximo de comecar';

    public function handle(): int
    {
        $janela = $this->option('minutos') !== null
            ? (int) $this->option('minutos')
            : (int) config('plantao.escala.lembrete_minutos_antes', 120);

        if ($janela <= 0) {
            $this->error('Janela de antecedencia precisa ser positiva.');

            return self::FAILURE;
        }

        $agora = Carbon::now();
        $limite = $agora->copy()->addMinutes($janela);

        $candidatos = $this->candidatos($agora, $limite);

        if ($candidatos->isEmpty()) {
            $this->info('Nenhum turno comecando nos proximos '.$janela.' minutos.');

            return self::SUCCESS;
        }

        $enviados = 0;

        foreach ($candidatos as $item) {
            $inicio = $item->inicioEm();

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '  [dry-run] %s -> %s (%s)',
                    $item->plantonista_nome,
                    $inicio?->format('d/m/Y H:i') ?? '?',
                    $item->tipoTurno?->labelCurto() ?? '?',
                ));

                continue;
            }

            if (!$this->marcarComoAvisado($item)) {
                // Outra execucao ganhou a corrida por esta linha.
                continue;
            }

            EntregarNotificacaoJob::dispatch(
                $this->spec($item, $inicio, $agora),
                [(int) $item->plantonista_id],
            );

            $enviados++;
        }

        $this->info($this->option('dry-run')
            ? $candidatos->count().' lembrete(s) seriam enviados.'
            : $enviados.' lembrete(s) enviado(s).');

        return self::SUCCESS;
    }

    /**
     * Vagas que podem gerar lembrete agora.
     *
     * O recorte por data e grosseiro de proposito -- de ontem ate depois de
     * amanha -- porque a hora de inicio vive na tabela de tipos e o turno que
     * vira o dia desloca o instante real. O filtro fino acontece em PHP, sobre
     * um punhado de linhas: sao no maximo alguns turnos por dia.
     *
     * @return \Illuminate\Support\Collection<int, EscalaItem>
     */
    private function candidatos(Carbon $agora, Carbon $limite): \Illuminate\Support\Collection
    {
        return EscalaItem::query()
            ->with(['tipoTurno', 'escala'])
            ->deEscalaPublicada()
            ->pendentes()
            ->whereNull('lembrete_enviado_em')
            ->entre($agora->copy()->subDay(), $limite->copy()->addDay())
            ->get()
            ->filter(function (EscalaItem $item) use ($agora, $limite): bool {
                $inicio = $item->inicioEm();

                // Tipo sem hora definida (EXTRAORDINARIO) nao gera lembrete:
                // nao ha instante para contar a antecedencia.
                if ($inicio === null) {
                    return false;
                }

                return $inicio->betweenIncluded($agora, $limite);
            })
            ->values();
    }

    /**
     * UPDATE condicional. Devolve false quando outra execucao ja marcou a linha.
     */
    private function marcarComoAvisado(EscalaItem $item): bool
    {
        return EscalaItem::query()
            ->whereKey($item->getKey())
            ->whereNull('lembrete_enviado_em')
            ->update(['lembrete_enviado_em' => Carbon::now()]) === 1;
    }

    private function spec(EscalaItem $item, ?Carbon $inicio, Carbon $agora): NotificacaoSpec
    {
        $faltam = $inicio === null ? null : (int) round($agora->diffInMinutes($inicio, false));

        $quando = match (true) {
            $faltam === null => 'em breve',
            $faltam <= 1 => 'agora',
            $faltam < 60 => 'em '.$faltam.' minutos',
            default => 'em '.round($faltam / 60).'h',
        };

        return new NotificacaoSpec(
            modulo: 'plantao',
            titulo: 'Seu plantao comeca '.$quando,
            mensagem: sprintf(
                'Turno de %s, %s. Assuma pelo modulo de Plantao ao chegar.',
                $item->data->format('d/m/Y'),
                $item->tipoTurno?->label() ?? 'horario a confirmar',
            ),
            tipo: 'urgent',
            // Sem agrupamento: lembrete e por vaga e nao deve ser fundido com o
            // card de publicacao da escala, que usa plantao:escala:{id}.
            groupKey: null,
            acaoUrl: '/plantao/escala',
            acaoTexto: 'Ver escala',
        );
    }
}
