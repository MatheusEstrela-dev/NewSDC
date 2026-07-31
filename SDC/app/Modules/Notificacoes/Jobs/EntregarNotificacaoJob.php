<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Jobs;

use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Services\NotificacaoDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fronteira entre o dominio e o sistema de notificacoes.
 *
 * Um gatilho de dominio (aprovacao de RAT, prazo de PAE, comentario em demanda)
 * faz uma unica coisa:
 *
 *     EntregarNotificacaoJob::dispatch($spec, $idsDosDestinatarios);
 *
 * e a requisicao do usuario termina ali. Resolver preferencias, agrupar, gravar,
 * transmitir por websocket e invalidar contador acontece no worker.
 *
 * Nao ha um job por destinatario: um job cobre o fan-out inteiro, em lotes. Assim
 * 500 destinatarios custam um job, e nao 500 (nem 1000, contando o broadcast).
 *
 * Propositalmente carrega apenas ids, nunca models: evita payload grande no Redis
 * e o classico model desatualizado (ou ja deletado) no momento em que o worker roda.
 */
class EntregarNotificacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tres tentativas e o job vai para failed_jobs (a dead letter queue), em vez
     * de ficar reciclando e travando a fila. Papiro: DLQ obrigatoria.
     */
    public int $tries = 3;

    /**
     * Uma notificacao atrasada perde valor: nao vale a pena insistir por muito tempo.
     */
    public int $timeout = 60;

    /**
     * @param  list<int>  $destinatarios  ids de usuario
     */
    public function __construct(
        public readonly NotificacaoSpec $spec,
        public readonly array $destinatarios,
    ) {
        $this->onQueue(
            $spec->ehUrgente()
                ? (string) config('notificacoes.entrega.fila_urgente', 'high')
                : (string) config('notificacoes.entrega.fila', 'default')
        );
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return (array) config('notificacoes.entrega.backoff_segundos', [10, 30, 60]);
    }

    public function handle(NotificacaoDispatcher $dispatcher): void
    {
        if ($this->destinatarios === []) {
            return;
        }

        $dispatcher->enviar($this->spec, $this->destinatarios);
    }

    /**
     * Registra o que nao foi entregue depois de esgotar as tentativas. A acao de
     * negocio que originou isso ja foi concluida: o que se perde e o aviso, e
     * precisa ficar rastreavel.
     */
    public function failed(?Throwable $e): void
    {
        Log::channel('jobs')->error('Notificacao nao entregue apos esgotar tentativas', [
            'modulo' => $this->spec->modulo,
            'titulo' => $this->spec->titulo,
            'group_key' => $this->spec->groupKey,
            'destinatarios' => count($this->destinatarios),
            'erro' => $e?->getMessage(),
        ]);
    }
}
