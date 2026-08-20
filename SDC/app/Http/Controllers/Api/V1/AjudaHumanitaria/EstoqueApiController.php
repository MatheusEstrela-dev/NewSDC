<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Services\SaldoCestaApiService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhSaldoCestaItem",
 *     type="object",
 *     title="Saldo de cesta basica por deposito",
 *     @OA\Property(property="id_deposito", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="BELO HORIZONTE"),
 *     @OA\Property(property="total_saldo", type="string", example="1240"),
 *     @OA\Property(property="singular", type="string", example="CESTA", description="aju_unidade.singular do legado. Veio esparso na carga; onde falta, o nome do material ocupa o lugar."),
 *     @OA\Property(property="valor", type="string", nullable=true, example="1.00"),
 *     @OA\Property(property="peso", type="integer", nullable=true, example=1, description="Peso truncado, equivalente ao floor() do legado.")
 * )
 */
final class EstoqueApiController extends Controller
{
    public function __construct(private readonly SaldoCestaApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/estoque/saldo-cesta",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Saldo de cesta basica por deposito",
     *     description="Paridade com o endpoint publico saldocesta do sistema legado, lendo do banco do NewSDC. Considera apenas material de categoria CESTA BASICA com saldo diferente de zero.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de saldos consolidados por deposito",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AhSaldoCestaItem"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function saldoCesta(): JsonResponse
    {
        return response()->json($this->servico->consultar());
    }
}
