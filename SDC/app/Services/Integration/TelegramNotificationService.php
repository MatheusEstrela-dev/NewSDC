<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Models\User;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servico de envio e pairing de mensagens Telegram per-user.
 *
 * Fluxo de conexao (pairing):
 *  1. User clica "Conectar" no SDC -> generatePairingCode($user) retorna codigo.
 *  2. Frontend mostra link t.me/<bot>?start=<code>.
 *  3. User clica e envia /start <code> ao bot no Telegram.
 *  4. Bot dispara webhook -> processInboundUpdate($update) cria UserIntegration
 *     verified com external_id = chat_id.
 *  5. Notificacoes futuras sao enviadas via TelegramChannel.
 *
 * HTTP direto via Laravel Http facade — nao depende de pacote externo.
 */
class TelegramNotificationService
{
    private const PAIRING_CACHE_PREFIX = 'telegram:pairing:';
    private const PAIRING_TTL_SECONDS = 600; // 10min

    /**
     * Envia mensagem de texto para o chat_id da integration.
     * Best-effort: erros sao logados, integration eh atualizada com
     * last_used_at mesmo em falha (para troubleshoot).
     *
     * @param array<string, mixed> $options parse_mode, reply_markup, etc.
     */
    public function send(UserIntegration $integration, string $text, array $options = []): bool
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            Log::warning('Telegram bot_token nao configurado em services.telegram.bot_token');
            return false;
        }

        $payload = array_merge([
            'chat_id' => $integration->external_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ], $options);

        try {
            $response = Http::timeout(10)
                ->post($this->apiUrl($botToken, 'sendMessage'), $payload);

            $integration->markAsUsed();

            if (!$response->successful()) {
                Log::warning('Telegram sendMessage falhou', [
                    'integration_id' => $integration->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage exception', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Gera codigo de pairing curto (8 chars) e armazena no Cache associado
     * ao user_id. User envia /start <code> ao bot dentro de 10min.
     */
    public function generatePairingCode(User $user): string
    {
        $code = strtoupper(Str::random(8));
        Cache::put(self::PAIRING_CACHE_PREFIX.$code, $user->id, self::PAIRING_TTL_SECONDS);
        return $code;
    }

    /**
     * Processa update inbound do bot Telegram. Identifica comando /start
     * com codigo de pairing valido, cria UserIntegration verified.
     *
     * @param array<string, mixed> $update payload do webhook Telegram
     */
    public function processInboundUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $text = (string) ($message['text'] ?? '');
        $chatId = $message['chat']['id'] ?? null;
        $username = $message['chat']['username'] ?? null;

        if ($chatId === null || !str_starts_with($text, '/start ')) {
            return;
        }

        $code = strtoupper(trim(substr($text, 7)));
        if ($code === '') {
            return;
        }

        $userId = Cache::pull(self::PAIRING_CACHE_PREFIX.$code);
        if ($userId === null) {
            Log::info('Telegram pairing code expirado ou invalido', ['code' => $code, 'chat_id' => $chatId]);
            return;
        }

        $integration = UserIntegration::updateOrCreate(
            [
                'user_id' => (int) $userId,
                'provider' => UserIntegration::PROVIDER_TELEGRAM,
                'external_id' => (string) $chatId,
            ],
            [
                'display_name' => $username ? "@{$username}" : null,
                'meta' => ['paired_via' => 'start_command'],
                'is_active' => true,
            ],
        );

        $integration->markAsVerified();

        Log::info('Telegram pairing concluido', [
            'user_id' => $userId,
            'chat_id' => $chatId,
            'integration_id' => $integration->id,
        ]);

        // Confirma para o user no proprio chat
        $this->send($integration, "Conta vinculada com sucesso ao SDC.\n\nVoce recebera aqui notificacoes de RAT, decretos e exports conforme suas preferencias em Configuracoes > Notificacoes.");
    }

    private function apiUrl(string $botToken, string $method): string
    {
        $base = rtrim((string) config('services.telegram.api_base_url', 'https://api.telegram.org'), '/');
        return "{$base}/bot{$botToken}/{$method}";
    }
}
