<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityFeedController extends Controller
{
    /**
     * Retorna o feed de atividades recentes do usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Implementar feed de atividades real.
        // Por enquanto retorna lista vazia para nao quebrar a rota.
        return response()->json([
            'data' => [],
            'meta' => [
                'total'   => 0,
                'message' => 'Activity feed ainda nao implementado.',
            ],
        ]);
    }
}
