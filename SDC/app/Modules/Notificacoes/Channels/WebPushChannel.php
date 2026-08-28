<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Channels;

use App\Modules\Notificacoes\Jobs\EnviarWebPushJob;
use Illuminate\Notifications\Notification;

/**
 * Entrega a notificacao como aviso do sistema operacional.
 *
 * O channel so monta o payload e enfileira: a conversa com o push service, o
 * retry e a limpeza de endpoint morto ficam no EnviarWebPushJob, pelo mesmo
 * motivo do e-mail -- o dispatcher entrega com sendNow() e nao pode ficar preso
 * esperando rede.
 */
class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWebPush')) {
            return;
        }

        $userId = $this->idDe($notifiable);
        if ($userId === null) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $notification->toWebPush($notifiable);

        EnviarWebPushJob::dispatch(
            $userId,
            $payload,
            ($payload['tipo'] ?? 'info') === 'urgent',
        );
    }

    private function idDe(object $notifiable): ?int
    {
        if (method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        return is_numeric($notifiable->id ?? null) ? (int) $notifiable->id : null;
    }
}
