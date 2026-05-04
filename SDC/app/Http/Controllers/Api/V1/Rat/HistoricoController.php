<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller stub para historico de ocorrencias RAT.
 * TODO: Implementar logica real.
 */
class HistoricoController extends Controller
{
    public function index(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function recent(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => []]);
    }
}
