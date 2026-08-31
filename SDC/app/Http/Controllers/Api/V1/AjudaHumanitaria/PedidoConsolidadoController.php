<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhPedidoConsolidadoItem",
 *     type="object",
 *     title="Item consolidado de pedido de ajuda humanitaria",
 *     @OA\Property(property="status", type="string", nullable=true, enum={"EdicaoCompdec","AnaliseDlog","AnaliseDiretorDlog","Aprovado","AguardandoDisponibilidade","AguardandoRetirada","Atendido","Cancelado","Reprovado","Finalizado"}, example="Atendido"),
 *     @OA\Property(property="descricao_item", type="string", example="CESTA BASICA"),
 *     @OA\Property(property="tp_item", type="string", nullable=true, enum={"P","L"}, example="L", description="P = pedido pelo municipio, L = liberado pelo CEDEC."),
 *     @OA\Property(property="municipio", type="string", example="Belo Horizonte"),
 *     @OA\Property(property="num_decreto", type="string", nullable=true, example="123"),
 *     @OA\Property(property="total_qtd", type="string", example="500")
 * )
 *
 * @OA\Schema(
 *     schema="AhPedidoConsolidadoPorMunicipio",
 *     type="object",
 *     title="Consolidado agrupado por municipio",
 *     description="Chave = nome do municipio. Formato do modo decreto_id.",
 *     @OA\AdditionalProperties(type="array", @OA\Items(ref="#/components/schemas/AhPedidoConsolidadoItem"))
 * )
 *
 * @OA\Schema(
 *     schema="AhPedidoConsolidadoLista",
 *     type="array",
 *     title="Consolidado em lista plana",
 *     description="Formato do modo bi.",
 *     @OA\Items(ref="#/components/schemas/AhPedidoConsolidadoItem")
 * )
 */
final class PedidoConsolidadoController extends Controller
{
    public function __construct(private readonly PedidoConsolidadoApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/pedidos/consolidado",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Consolidado de itens de pedido",
     *     description="Paridade com o endpoint listPedidoAh do sistema legado. Informe decreto_id (agrupa por municipio, somente pedidos Atendido ou Finalizado) ou bi (lista plana, sem recorte de status). Retorna vazio enquanto o historico de pedidos do legado nao estiver carregado no banco novo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="decreto_id", in="query", required=false, description="Numero do decreto. Obrigatorio se bi nao for informado", @OA\Schema(type="string", example="123")),
     *     @OA\Parameter(name="bi", in="query", required=false, description="Qualquer valor ativa o modo plano. Obrigatorio se decreto_id nao for informado", @OA\Schema(type="string", example="1")),
     *     @OA\Response(
     *         response=200,
     *         description="Modo decreto_id devolve objeto com chave = municipio; modo bi devolve array plano",
     *         @OA\JsonContent(oneOf={
     *             @OA\Schema(ref="#/components/schemas/AhPedidoConsolidadoPorMunicipio"),
     *             @OA\Schema(ref="#/components/schemas/AhPedidoConsolidadoLista")
     *         })
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.pedidos.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Nem decreto_id nem bi informado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function __invoke(ConsultaPedidoConsolidadoRequest $request): JsonResponse
    {
        if ($request->modoBi()) {
            return response()->json($this->servico->paraBi());
        }

        return response()->json($this->servico->porDecreto((string) $request->decretoId()));
    }
}
