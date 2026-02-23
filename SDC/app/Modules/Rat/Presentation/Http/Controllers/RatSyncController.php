<?php

declare(strict_types=1);

namespace App\Modules\Rat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Domain\Entities\Rat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RatSyncController extends Controller
{
    /**
     * Recebe dados sincronizados do modo offline.
     */
    public function sync(Request $request): JsonResponse
    {
        // Validação básica
        $request->validate([
            'id' => 'required|uuid', // Valida formato UUID
            'protocolo' => 'nullable|string',
            'dadosGerais' => 'nullable|array',
            'created_at' => 'nullable|date',
        ]);

        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verifica se o RAT já existe para checar permissão
        $existingRat = Rat::find($request->id);

        if ($existingRat) {
            // Se existe, verifica se o usuário pode alterar
            // Idealmente: $this->authorize('update', $existingRat);
            // Como fallback simples: verifica se é o dono
            if ($existingRat->user_id !== $user->id && !$user->hasRole('admin')) {
                return response()->json(['message' => 'Unauthorized access to this RAT'], 403);
            }
        }

        $rat = Rat::updateOrCreate(
            ['id' => $request->id],
            [
                'protocolo' => $request->protocolo,
                'status' => 'recebido_offline',
                'dados_gerais' => $request->dadosGerais,
                'user_id' => $user->id, // Usa ID do usuário autenticado
                'created_at' => $request->created_at,
            ]
        );

        return response()->json([
            'message' => 'RAT sincronizado com sucesso!',
            'id' => $rat->id
        ], 200);
    }
}
