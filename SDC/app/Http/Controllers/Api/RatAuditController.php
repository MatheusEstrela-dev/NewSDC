<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller stub para auditoria de RATs.
 * Registra e exibe o historico de alteracoes nos registros RAT.
 * TODO: Implementar logica real de auditoria.
 */
class RatAuditController extends Controller
{
    /**
     * Lista entradas de auditoria com filtros opcionais.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => [
                'total'   => 0,
                'message' => 'Auditoria RAT ainda nao implementada.',
            ],
        ]);
    }

    /**
     * Exibe o historico de auditoria de um RAT especifico.
     */
    public function show(string $id): JsonResponse
    {
        return response()->json([
            'data'    => [],
            'message' => 'Auditoria RAT ainda nao implementada.',
        ]);
    }
}
