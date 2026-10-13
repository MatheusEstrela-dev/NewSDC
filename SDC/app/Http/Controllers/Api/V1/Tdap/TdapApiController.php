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
 *
 * @OA\Tag(
 *     name="TDAP",
 *     description="Transporte e Distribuição de Água Potável (carro-pipa) — viagens validadas para Power BI"
 * )
 *
 * @OA\Schema(
 *     schema="TdapViagemValidada",
 *     type="object",
 *     description="Uma viagem validada de carro-pipa, com cronograma, ata, lote, município, prestador e caminhão denormalizados",
 *     @OA\Property(property="viagem_id", type="integer", example=9812),
 *     @OA\Property(property="data_registro", type="string", format="date-time", nullable=true, example="2026-09-20T08:30:00-03:00"),
 *     @OA\Property(property="data_aprovacao", type="string", format="date-time", nullable=true, example="2026-09-21T14:00:00-03:00"),
 *     @OA\Property(property="cronograma_id", type="integer", example=120),
 *     @OA\Property(property="cronograma_numero", type="string", nullable=true, example="CR-2026-0120"),
 *     @OA\Property(property="empenho", type="string", nullable=true),
 *     @OA\Property(property="nota_empenho", type="string", nullable=true),
 *     @OA\Property(property="cronograma_inicio", type="string", format="date", nullable=true, example="2026-09-01"),
 *     @OA\Property(property="cronograma_final", type="string", format="date", nullable=true, example="2026-09-30"),
 *     @OA\Property(property="ata_id", type="integer", example=7),
 *     @OA\Property(property="ata_numero", type="string", nullable=true, example="05/2026"),
 *     @OA\Property(property="lote_id", type="integer", example=3),
 *     @OA\Property(property="lote_numero", type="string", nullable=true, example="Lote 03"),
 *     @OA\Property(property="municipio_id", type="integer", example=3106200),
 *     @OA\Property(property="municipio_nome", type="string", example="Montes Claros"),
 *     @OA\Property(property="municipio_uf", type="string", example="MG"),
 *     @OA\Property(property="prestador_id", type="integer", example=15),
 *     @OA\Property(property="prestador_nome", type="string", example="Transportes Exemplo Ltda"),
 *     @OA\Property(property="prestador_cnpj", type="string", example="00.000.000/0001-00"),
 *     @OA\Property(property="caminhao_id", type="integer", example=44),
 *     @OA\Property(property="caminhao_placa", type="string", example="ABC1D23"),
 *     @OA\Property(property="capacidade_m3", type="number", format="float", example=16),
 *     @OA\Property(property="valor_m3", type="number", format="float", example=12.5),
 *     @OA\Property(property="valor_viagem", type="number", format="float", description="capacidade_m3 x valor_m3, 2 casas decimais", example=200)
 * )
 */
class TdapApiController extends Controller
{
    public function __construct(
        private readonly TdapExportBiService $bi,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/tdap/cronogramas",
     *     operationId="tdapCronogramas",
     *     tags={"TDAP"},
     *     summary="Viagens validadas dos cronogramas TDAP (Power BI)",
     *     description="Lista flat com uma linha por viagem validada, ordenada pela data de aprovação. Aceita sessão web do sistema ou Bearer token Sanctum.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="format", in="query", required=false, description="Formato de saída", @OA\Schema(type="string", enum={"json","powerbi"}, example="powerbi")),
     *     @OA\Parameter(name="de", in="query", required=false, description="Data de aprovação mínima (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2026-09-01")),
     *     @OA\Parameter(name="ate", in="query", required=false, description="Data de aprovação máxima (YYYY-MM-DD), maior ou igual a `de`", @OA\Schema(type="string", format="date", example="2026-09-30")),
     *     @OA\Parameter(name="prestador_id", in="query", required=false, description="Filtrar por prestador", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, description="Filtrar por município", @OA\Schema(type="integer", example=3106200)),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Máximo de linhas (padrão: 10000, máx: 50000)", @OA\Schema(type="integer", example=10000)),
     *     @OA\Response(
     *         response=200,
     *         description="Viagens validadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TdapViagemValidada")),
     *             @OA\Property(property="total", type="integer", example=1),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="generated_at", type="string", format="date-time"),
     *                 @OA\Property(property="format", type="string", example="powerbi")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Bearer token ausente ou inválido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Parâmetros inválidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit atingido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
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
