<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tdap;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Services\TdapExportBiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint AJAX/PowerBI para leitura agregada do TDAP-Agua.
 *
 * Suporta query `format=powerbi` para retornar lista flat (1 linha por
 * viagem validada com colunas denormalizadas).
 *
 * Auth via middleware api.token (reaproveita o padrao usado em /api/v1/rat/*).
 */
class TdapApiController extends Controller
{
    public function __construct(
        private readonly TdapExportBiService $bi,
    ) {}

    public function cronogramas(Request $request): JsonResponse
    {
        $request->validate([
            'format'        => ['nullable', 'string', 'in:powerbi,json'],
            'de'            => ['nullable', 'date'],
            'ate'           => ['nullable', 'date', 'after_or_equal:de'],
            'prestador_id'  => ['nullable', 'integer'],
            'municipio_id'  => ['nullable', 'integer'],
            'limit'         => ['nullable', 'integer', 'min:1', 'max:50000'],
        ]);

        $rows = $this->bi->viagensValidadasFlat($request->only(['de', 'ate', 'prestador_id', 'municipio_id', 'limit']));

        return response()->json([
            'data'  => $rows,
            'total' => count($rows),
            'meta'  => [
                'generated_at' => now()->toIso8601String(),
                'format'       => $request->input('format', 'json'),
            ],
        ]);
    }
}
