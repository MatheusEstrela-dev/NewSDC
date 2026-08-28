<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registra e remove os dispositivos que recebem Web Push.
 *
 * Fluxo: o navegador pede permissao, o PushManager devolve um endpoint com duas
 * chaves, e o cliente manda isso para ca. O endpoint e a identidade -- se ja
 * existir, a linha e atualizada em vez de duplicada, o que tambem cobre a troca
 * de dono do dispositivo (maquina compartilhada).
 *
 * O cliente reenvia o endpoint a cada boot. Isso nao e redundancia: o app.js
 * desregistra o service worker na recuperacao de build velho e no 419, e o
 * navegador volta com endpoint novo. Sem o reenvio, o push silenciosamente
 * pararia de funcionar depois do primeiro 419.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $dados = $request->validate([
            'endpoint' => ['required', 'string', 'max:500', 'url'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['sometimes', 'string', 'max:20'],
        ]);

        $inscricao = PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashDoEndpoint($dados['endpoint'])],
            [
                'user_id' => $user->id,
                'endpoint' => $dados['endpoint'],
                'public_key' => $dados['keys']['p256dh'],
                'auth_token' => $dados['keys']['auth'],
                'content_encoding' => $dados['content_encoding'] ?? 'aesgcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_used_at' => now(),
            ],
        );

        $this->podarExcedentes((int) $user->id, (int) $inscricao->getKey());

        return response()->json([
            'message' => 'Dispositivo registrado.',
            'dispositivos' => $this->dispositivos((int) $user->id),
        ]);
    }

    /**
     * Remove a inscricao deste navegador (o cliente manda o proprio endpoint) ou,
     * com id, um dispositivo escolhido na lista de Configuracoes.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $dados = $request->validate([
            'endpoint' => ['sometimes', 'string', 'max:500'],
            'id' => ['sometimes', 'integer'],
        ]);

        $query = PushSubscription::query()->forUser((int) $user->id);

        if (isset($dados['id'])) {
            $query->whereKey($dados['id']);
        } elseif (isset($dados['endpoint'])) {
            $query->where('endpoint_hash', PushSubscription::hashDoEndpoint($dados['endpoint']));
        } else {
            return response()->json(['error' => 'Informe endpoint ou id.'], 422);
        }

        $query->delete();

        return response()->json([
            'message' => 'Dispositivo removido.',
            'dispositivos' => $this->dispositivos((int) $user->id),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json(['dispositivos' => $this->dispositivos((int) $user->id)]);
    }

    /**
     * Mantem no maximo N inscricoes por usuario, descartando as mais antigas.
     *
     * O teto existe porque cada desregistro de service worker gera um endpoint
     * novo: sem poda, um usuario que passa por varios 419 acumularia dezenas de
     * linhas mortas, e cada envio pagaria uma requisicao HTTP por linha.
     */
    private function podarExcedentes(int $userId, int $manterId): void
    {
        $limite = max(1, (int) config('webpush.max_por_usuario', 10));

        $excedentes = PushSubscription::query()
            ->forUser($userId)
            ->whereKeyNot($manterId)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->skip($limite - 1)
            ->take(100)
            ->pluck('id');

        if ($excedentes->isNotEmpty()) {
            PushSubscription::query()->whereIn('id', $excedentes)->delete();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dispositivos(int $userId): array
    {
        return PushSubscription::query()
            ->forUser($userId)
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (PushSubscription $s): array => [
                'id' => $s->getKey(),
                'apelido' => $s->apelido(),
                'ultimo_uso' => $s->last_used_at?->toIso8601String(),
            ])
            ->all();
    }
}
