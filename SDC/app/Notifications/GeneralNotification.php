<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserNotificationPreference;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info', // info, success, warning, error, urgent
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public ?string $groupKey = null // For grouping similar notifications
    ) {}

    /**
     * Resolve canais dinamicamente conforme preferencias do user para o
     * modulo derivado do groupKey. database e broadcast sao sempre incluidos
     * (inbox do app + push websocket); telegram/whatsapp/email entram apenas
     * se o user habilitou em Configuracoes > Notificacoes.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        $userId = $this->extractUserId($notifiable);
        if ($userId === null) {
            return $channels;
        }

        $module = $this->moduleFromGroupKey();
        if ($module === null) {
            return $channels;
        }

        $pref = UserNotificationPreference::query()
            ->where('user_id', $userId)
            ->where('module', $module)
            ->first();

        if ($pref === null) {
            return $channels;
        }

        if ($pref->canal_telegram ?? false) {
            $channels[] = TelegramChannel::class;
        }
        // canal_whatsapp + canal_email entram quando os channels existirem.

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'group_key' => $this->groupKey,
            'created_at' => now(),
        ];
    }

    /**
     * Payload para o TelegramChannel.
     *
     * @return array{text: string, parse_mode: string}
     */
    public function toTelegram(object $notifiable): array
    {
        $emoji = match ($this->type) {
            'success' => '[OK]',
            'warning' => '[!]',
            'error' => '[X]',
            'urgent' => '[URGENTE]',
            default => '[INFO]',
        };

        $lines = [
            "*{$emoji} {$this->escapeMarkdown($this->title)}*",
            '',
            $this->escapeMarkdown($this->message),
        ];

        if ($this->actionUrl) {
            $label = $this->actionText ?: 'Acessar';
            $lines[] = '';
            $lines[] = "[{$this->escapeMarkdown($label)}]({$this->actionUrl})";
        }

        return [
            'text' => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ];
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

    /**
     * Mapeia groupKey para um modulo conhecido em UserNotificationPreference.
     * Convencao: groupKey comeca com "rat:", "decretacoes:", "trace:", etc.
     */
    private function moduleFromGroupKey(): ?string
    {
        if ($this->groupKey === null || $this->groupKey === '') {
            return null;
        }

        $prefix = strtolower(strtok($this->groupKey, ':'));
        $map = [
            'rat' => 'rat',
            'pae' => 'pae',
            'meteorologia' => 'meteorologia',
            'demandas' => 'demandas',
            'decretacoes' => 'decretacoes',
            'trace' => null, // exports/async — usa preferencia geral (sem module)
        ];

        return $map[$prefix] ?? null;
    }

    private function escapeMarkdown(string $text): string
    {
        // Markdown legacy do Telegram aceita * _ [ ] ( ); escape minimo.
        return str_replace(['_', '*', '[', ']'], ['\\_', '\\*', '\\[', '\\]'], $text);
    }
}
