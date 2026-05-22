<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integration\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint publico de webhook do bot Telegram. URL inclui webhook_secret
 * no path para autenticacao primaria; sem secret correto, retorna 404.
 *
 * Configurado no Telegram via setWebhook (uma vez por deploy):
 *   curl https://api.telegram.org/bot{TOKEN}/setWebhook?url={APP_URL}/api/integrations/telegram/webhook/{SECRET}
 */
class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramNotificationService $service,
    ) {}

    public function handle(Request $request, string $secret): JsonResponse
    {
        $expected = (string) config('services.telegram.webhook_secret', '');

        if ($expected === '' || !hash_equals($expected, $secret)) {
            Log::warning('Telegram webhook chamado com secret invalido', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Not Found'], 404);
        }

        $update = $request->all();

        try {
            $this->service->processInboundUpdate($update);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook processing failed', [
                'error' => $e->getMessage(),
                'update_id' => $update['update_id'] ?? null,
            ]);
            // Retorna 200 mesmo em erro para Telegram nao re-enviar indefinidamente.
        }

        return response()->json(['ok' => true]);
    }
}
