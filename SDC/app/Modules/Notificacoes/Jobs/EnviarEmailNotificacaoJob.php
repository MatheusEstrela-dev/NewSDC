<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Jobs;

use App\Models\User;
use App\Modules\Notificacoes\Channels\EmailNotificacaoChannel;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\GeneralNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Enfileira o e-mail de notificacao para um LOTE de destinatarios.
 *
 * Existe para o dispatcher nao criar um job por pessoa. Um alerta para mil
 * usuarios gerava mil jobs so para decidir e montar mensagem; agora sao
 * mil/chunk, e cada um resolve seus destinatarios numa query.
 *
 * O envio em si continua sendo uma mensagem por destinatario, de proposito: a
 * falha de SMTP para um endereco nao pode arrastar os outros do lote para o
 * retry. Quem faz isso e o EmailNotificacaoChannel, reaproveitado aqui inteiro
 * -- inclusive a dedup por janela, que assim vale nos dois caminhos.
 */
class EnviarEmailNotificacaoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        private readonly array $userIds,
        private readonly NotificacaoSpec $spec,
    ) {
        $this->onQueue($spec->ehUrgente()
            ? (string) config('notificacoes.entrega.fila_urgente', 'notificacoes_urgente')
            : (string) config('notificacoes.entrega.fila', 'notificacoes'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return (array) config('notificacoes.entrega.backoff_segundos', [10, 30, 60]);
    }

    public function handle(EmailNotificacaoChannel $canal): void
    {
        if ($this->userIds === []) {
            return;
        }

        $notificacao = GeneralNotification::deSpec($this->spec, [EmailNotificacaoChannel::class]);

        User::query()
            ->whereIn('id', $this->userIds)
            ->each(fn (User $usuario) => $canal->send($usuario, $notificacao));
    }
}
