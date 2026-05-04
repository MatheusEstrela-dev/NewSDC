<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    /**
     * Retorna as preferencias de notificacao do usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Implementar preferencias de notificacao reais.
        return response()->json([
            'data' => [
                'email'  => true,
                'push'   => false,
                'sms'    => false,
            ],
        ]);
    }

    /**
     * Atualiza as preferencias de notificacao do usuario autenticado.
     */
    public function update(Request $request): JsonResponse
    {
        // TODO: Implementar atualizacao de preferencias reais.
        return response()->json([
            'message' => 'Preferencias atualizadas com sucesso.',
            'data'    => $request->only(['email', 'push', 'sms']),
        ]);
    }
}
