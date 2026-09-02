<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Console;

use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Models\ViaturaReserva;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Solta a agenda presa por reserva que ninguem retirou.
 *
 * A reserva e obrigatoria para tirar a chave, entao uma reserva esquecida
 * bloqueia a viatura para todo mundo. O carrasco roda a cada 15 minutos e so
 * toca em AGENDADA: reserva EM_USO tem chave na mao de alguem e viatura em
 * transito -- expira-la mentiria sobre o estado da frota, e o caminho dela e o
 * check-out.
 *
 * A folga depois do fim previsto existe porque o atraso e o caso normal, nao a
 * excecao: quem reservou ate as 18h e chegou 18h05 ainda deve conseguir a
 * chave. Passado o prazo, EXPIRADA -- distinta de CANCELADA de proposito, para
 * que o relatorio consiga separar "desistiu" de "nao apareceu".
 *
 * IDEMPOTENCIA, pelo mesmo desenho de LembrarEscalaCommand: a transicao e um
 * UPDATE condicional (... WHERE status = 'AGENDADA') pelo query builder. Duas
 * execucoes simultaneas disputam a linha e so uma afeta. Query builder e nao
 * Eloquent tambem evita que a trilha de acoes vire ruido -- expirar e ato do
 * relogio, nao de uma pessoa.
 */
class ExpirarReservasCommand extends Command
{
    protected $signature = 'plantao:expirar-reservas
        {--minutos= : Sobrescreve a folga apos o fim previsto, em minutos}
        {--dry-run : Lista o que seria expirado sem gravar}';

    protected $description = 'Expira reservas de viatura cuja janela venceu sem retirada da chave';

    public function handle(): int
    {
        $folga = $this->option('minutos') !== null
            ? (int) $this->option('minutos')
            : (int) config('plantao.reservas.expiracao_apos_fim_minutos', 60);

        if ($folga < 0) {
            $this->error('A folga apos o fim previsto nao pode ser negativa.');

            return self::FAILURE;
        }

        $limite = Carbon::now()->subMinutes($folga);

        $candidatas = ViaturaReserva::query()
            ->with('viatura:id,prefixo,placa')
            ->where('status', StatusReserva::AGENDADA->value)
            ->where('fim_previsto', '<', $limite)
            ->orderBy('fim_previsto')
            ->get();

        if ($candidatas->isEmpty()) {
            $this->info('Nenhuma reserva vencida ha mais de '.$folga.' minutos.');

            return self::SUCCESS;
        }

        $expiradas = 0;

        foreach ($candidatas as $reserva) {
            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '  [dry-run] %s - %s ate %s (%s)',
                    $reserva->viatura?->placa ?? '?',
                    $reserva->agente_nome,
                    $reserva->fim_previsto->format('d/m/Y H:i'),
                    $reserva->destino ?? 'sem destino',
                ));

                continue;
            }

            if (!$this->marcarExpirada($reserva)) {
                // Outra execucao ganhou a corrida, ou o agente fez o check-in
                // entre a leitura e a escrita.
                continue;
            }

            $this->avisar($reserva);
            $expiradas++;
        }

        $this->info($this->option('dry-run')
            ? $candidatas->count().' reserva(s) seriam expiradas.'
            : $expiradas.' reserva(s) expirada(s).');

        return self::SUCCESS;
    }

    /**
     * UPDATE condicional. Devolve false quando outra execucao ja transicionou a
     * linha -- ou quando o agente fez o check-in entre a leitura e a escrita,
     * que e a corrida que importa evitar: expirar uma reserva ja em uso
     * apagaria o vinculo com a movimentacao aberta.
     */
    /**
     * Avisa o agente de que a reserva dele caiu.
     *
     * Despachado DEPOIS da marca, como em LembrarEscalaCommand: perder um aviso
     * por falha de fila e recuperavel (o job tem tentativas e DLQ), enquanto
     * mandar o mesmo aviso a cada passo de 15 minutos ensinaria a ignorar o
     * sino. Tipo `info` e nao `urgent` -- ao contrario do cancelamento por
     * terceiro, aqui o prazo simplesmente passou, e nada mais depende de acao
     * imediata.
     */
    private function avisar(ViaturaReserva $reserva): void
    {
        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'plantao',
                titulo: 'Sua reserva de viatura expirou',
                mensagem: sprintf(
                    'A reserva da %s terminava %s e a chave nao foi retirada. A viatura voltou a ficar livre.',
                    $reserva->viatura?->placa ?? 'viatura',
                    $reserva->fim_previsto->format('d/m \a\s H:i'),
                ),
                tipo: 'info',
                groupKey: null,
                acaoUrl: '/plantao/reservas',
                acaoTexto: 'Ver reservas',
            ),
            [(int) $reserva->agente_id],
        );
    }

    private function marcarExpirada(ViaturaReserva $reserva): bool
    {
        return ViaturaReserva::query()
            ->whereKey($reserva->getKey())
            ->where('status', StatusReserva::AGENDADA->value)
            ->update(['status' => StatusReserva::EXPIRADA->value]) === 1;
    }
}
