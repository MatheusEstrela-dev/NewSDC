<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gerencia preferencias de notificacao por modulo e canal do user.
 * Schema: 1 row por (user, module) com booleans por canal:
 * sistema (inbox), email, push, telegram, whatsapp.
 *
 * Default em UserNotificationPreference::forUser cria todas as MODULES
 * com sistema=true e demais canais=false (opt-in para canais externos).
 */
class NotificationPreferencesController extends Controller
{
    /**
     * Lista preferencias do user (auto-cria defaults se vazio).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $prefs = UserNotificationPreference::forUser($user->id);

        return response()->json([
            'modules' => $prefs->map(fn (UserNotificationPreference $p) => [
                'module' => $p->module,
                'canal_sistema' => $p->canal_sistema,
                'canal_email' => $p->canal_email,
                'canal_push' => $p->canal_push,
                'canal_telegram' => $p->canal_telegram,
                'canal_whatsapp' => $p->canal_whatsapp,
            ])->values(),
        ]);
    }

    /**
     * Atualiza preferencias em batch. Payload esperado:
     * {
     *   "modules": [
     *     {"module": "rat", "canal_sistema": true, "canal_telegram": true, ...},
     *     ...
     *   ]
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
            'modules.*.module' => ['required', 'string', 'in:'.implode(',', UserNotificationPreference::MODULES)],
            'modules.*.canal_sistema' => ['sometimes', 'boolean'],
            'modules.*.canal_email' => ['sometimes', 'boolean'],
            'modules.*.canal_push' => ['sometimes', 'boolean'],
            'modules.*.canal_telegram' => ['sometimes', 'boolean'],
            'modules.*.canal_whatsapp' => ['sometimes', 'boolean'],
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

        return response()->json([
            'message' => 'Preferencias atualizadas.',
            'modules' => UserNotificationPreference::forUser($user->id)->map(
                fn (UserNotificationPreference $p) => [
                    'module' => $p->module,
                    'canal_sistema' => $p->canal_sistema,
                    'canal_email' => $p->canal_email,
                    'canal_push' => $p->canal_push,
                    'canal_telegram' => $p->canal_telegram,
                    'canal_whatsapp' => $p->canal_whatsapp,
                ]
            )->values(),
        ]);
    }
}
