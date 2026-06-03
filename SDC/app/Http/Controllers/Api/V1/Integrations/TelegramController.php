<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integrations;

use App\Http\Controllers\Controller;
use App\Models\UserIntegration;
use App\Services\Integration\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gerencia integracao Telegram do usuario autenticado.
 *
 * Fluxo:
 *  - POST /connect    -> gera pairing code + link t.me/<bot>?start=<code>
 *  - GET  /status     -> cliente polla ate status=connected (pos /start no bot)
 *  - DELETE /{id}     -> desconecta integration
 */
class TelegramController extends Controller
{
    public function __construct(
        private readonly TelegramNotificationService $service,
    ) {}

    /**
     * Inicia processo de pairing. Retorna codigo + link clicavel.
     */
    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $botUsername = config('services.telegram.bot_username');
        if (!$botUsername) {
            return response()->json([
                'error' => 'Service Misconfigured',
                'message' => 'TELEGRAM_BOT_USERNAME nao configurado no servidor.',
            ], 503);
        }

        $code = $this->service->generatePairingCode($user);

        return response()->json([
            'code' => $code,
            'link' => "https://t.me/{$botUsername}?start={$code}",
            'expires_in_seconds' => 600,
            'instructions' => 'Clique no link, envie /start ao bot Telegram para concluir a vinculacao.',
        ]);
    }

    /**
     * Retorna status atual da integration Telegram do user (polling).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $integration = UserIntegration::query()
            ->forUser($user->id)
            ->provider(UserIntegration::PROVIDER_TELEGRAM)
            ->active()
            ->latest()
            ->first();

        if ($integration === null) {
            return response()->json(['status' => 'not_connected']);
        }

        return response()->json([
            'status' => $integration->isVerified() ? 'connected' : 'awaiting_pairing',
            'integration' => [
                'id' => $integration->id,
                'display_name' => $integration->display_name,
                'verified_at' => $integration->verified_at?->toIso8601String(),
                'last_used_at' => $integration->last_used_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Desconecta integration (soft: marca is_active=false; conta permanece
     * na tabela para historico).
     */
    public function disconnect(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $integration = UserIntegration::query()
            ->forUser($user->id)
            ->provider(UserIntegration::PROVIDER_TELEGRAM)
            ->find($id);

        if ($integration === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $integration->update(['is_active' => false]);

        return response()->json(['status' => 'disconnected']);
    }
}
