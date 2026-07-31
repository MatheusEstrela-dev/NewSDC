<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gerencia preferencias de notificacao por modulo e canal do user.
 *
 * Schema: 1 row por (user, module) com booleans por canal: sistema (inbox),
 * email, push, telegram, whatsapp. Default em UserNotificationPreference::forUser
 * cria todos os modulos de config('notificacoes.modulos') com sistema=true e
 * canais externos em false (opt-in explicito).
 *
 * A resposta tambem carrega o rotulo e a descricao de cada modulo, vindos do
 * config. Antes, o frontend mantinha essa lista hardcoded em SettingsModal.vue e
 * ela divergia da lista aceita pelo backend.
 */
class NotificationPreferencesController extends Controller
{
    private const MODOS = ['auto', 'realtime', 'polling'];

    /**
     * Lista preferencias do user (auto-cria defaults se vazio).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'modules' => $this->modulos($user->id),
            'update_mode' => $user->notification_update_mode ?? 'auto',
        ]);
    }

    /**
     * Atualiza preferencias em batch. Payload esperado:
     * {
     *   "modules": [
     *     {"module": "rat", "canal_sistema": true, "canal_telegram": true, ...},
     *     ...
     *   ],
     *   "update_mode": "auto"
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.module' => ['required', 'string', 'in:'.implode(',', UserNotificationPreference::modules())],
            'modules.*.canal_sistema' => ['sometimes', 'boolean'],
            'modules.*.canal_email' => ['sometimes', 'boolean'],
            'modules.*.canal_push' => ['sometimes', 'boolean'],
            'modules.*.canal_telegram' => ['sometimes', 'boolean'],
            'modules.*.canal_whatsapp' => ['sometimes', 'boolean'],
            'update_mode' => ['sometimes', 'string', 'in:'.implode(',', self::MODOS)],
        ]);

        foreach ($validated['modules'] as $row) {
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'module' => $row['module']],
                array_intersect_key($row, array_flip([
                    'canal_sistema', 'canal_email', 'canal_push',
                    'canal_telegram', 'canal_whatsapp',
                ])),
            );
        }

        // Como o cliente recebe as notificacoes (websocket ou polling). O campo ja
        // era enviado pelo SettingsModal, mas antes nao havia coluna para gravar.
        if (array_key_exists('update_mode', $validated)) {
            $user->forceFill(['notification_update_mode' => $validated['update_mode']])->save();
        }

        return response()->json([
            'message' => 'Preferencias atualizadas.',
            'modules' => $this->modulos($user->id),
            'update_mode' => $user->notification_update_mode ?? 'auto',
        ]);
    }

    /**
     * Preferencias do usuario enriquecidas com os metadados do modulo.
     *
     * @return list<array<string, mixed>>
     */
    private function modulos(int $userId): array
    {
        $catalogo = (array) config('notificacoes.modulos', []);

        return UserNotificationPreference::forUser($userId)
            ->map(fn (UserNotificationPreference $p): array => [
                'module' => $p->module,
                'label' => $catalogo[$p->module]['label'] ?? $p->module,
                'descricao' => $catalogo[$p->module]['descricao'] ?? '',
                'icone' => $catalogo[$p->module]['icone'] ?? 'BellIcon',
                'canal_sistema' => $p->canal_sistema,
                'canal_email' => $p->canal_email,
                'canal_push' => $p->canal_push,
                'canal_telegram' => $p->canal_telegram,
                'canal_whatsapp' => $p->canal_whatsapp,
            ])
            ->values()
            ->all();
    }
}
