<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\UserIntegration;
use App\Services\Integration\TelegramNotificationService;
use Illuminate\Notifications\Notification;

/**
 * Custom Notification Channel para Telegram.
 *
 * Notifications que retornarem array de `via($notifiable)` contendo 'telegram'
 * passam por aqui. O channel busca UserIntegration ativa e verified do user,
 * chama Notification::toTelegram($notifiable) para obter o payload, e delega
 * ao TelegramNotificationService.
 *
 * Se o user nao tem integration Telegram ativa, skip silencioso.
 */
class TelegramChannel
{
    public function __construct(
        private readonly TelegramNotificationService $service,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $userId = $this->extractUserId($notifiable);
        if ($userId === null) {
            return;
        }

        $integration = UserIntegration::query()
            ->forUser($userId)
            ->provider(UserIntegration::PROVIDER_TELEGRAM)
            ->active()
            ->verified()
            ->first();

        if ($integration === null) {
            return;
        }

        /** @var array{text: string, parse_mode?: string, reply_markup?: array} $payload */
        $payload = $notification->toTelegram($notifiable);

        $text = $payload['text'] ?? null;
        if (!is_string($text) || $text === '') {
            return;
        }

        $options = [];
        if (isset($payload['parse_mode'])) {
            $options['parse_mode'] = $payload['parse_mode'];
        }
        if (isset($payload['reply_markup'])) {
            $options['reply_markup'] = $payload['reply_markup'];
        }

        $this->service->send($integration, $text, $options);
    }

    private function extractUserId(object $notifiable): ?int
    {
        if (property_exists($notifiable, 'id') && is_numeric($notifiable->id ?? null)) {
            return (int) $notifiable->id;
        }
        if (method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();
            return is_numeric($key) ? (int) $key : null;
        }
        return null;
    }
}
