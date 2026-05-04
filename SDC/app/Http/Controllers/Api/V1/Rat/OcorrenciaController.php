<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller stub para ocorrencias RAT (nova estrutura polimorfica).
 * TODO: Implementar logica real.
 */
class OcorrenciaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Nao implementado.'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Nao implementado.'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Nao implementado.'], 501);
    }

    public function finalize(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Nao implementado.'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Nao implementado.'], 501);
    }
}
