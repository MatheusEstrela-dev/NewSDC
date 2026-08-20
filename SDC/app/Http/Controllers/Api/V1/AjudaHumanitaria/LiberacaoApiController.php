<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhLiberacaoItem",
 *     type="object",
 *     title="Liberacao de material de ajuda humanitaria",
 *     @OA\Property(property="id_liberacao", type="integer", nullable=true, example=3421, description="Identificador do sistema legado, preservado para consumidores de BI."),
 *     @OA\Property(property="data_liberacao", type="string", format="date", example="2022-03-10"),
 *     @OA\Property(property="hora_liberacao", type="string", nullable=true, example=null),
 *     @OA\Property(property="mes", type="integer", example=3),
 *     @OA\Property(property="evento", type="string", nullable=true, enum={"AJUDA HUMANITARIA","CEDEC","CHUVA","COVID-19","OUTROS","SECA"}, example="SECA"),
 *     @OA\Property(property="situacao", type="string", enum={"Aberto","Pago","Cancelado","Desconhecido"}, example="Pago"),
 *     @OA\Property(property="unidade", type="object",
 *         @OA\Property(property="id_municipio", type="integer", example=123),
 *         @OA\Property(property="codmundv", type="string", nullable=true, example="3106200"),
 *         @OA\Property(property="nome", type="string", example="Belo Horizonte")
 *     ),
 *     @OA\Property(property="items_quant", type="integer", example=0, description="Soma das quantidades dos itens. Zero enquanto a carga de itens do legado nao estiver concluida."),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object"), description="Vazio enquanto ajuda_h_liberacao_itens nao tiver carga.")
 * )
 *
 * @OA\Schema(
 *     schema="AhLiberacaoCedecItem",
 *     type="object",
 *     title="Liberacao no formato plano do CEDEC",
 *     @OA\Property(property="id_municipio", type="integer", example=123),
 *     @OA\Property(property="Codmundv", type="string", nullable=true, example="3106200"),
 *     @OA\Property(property="municipio", type="string", example="Belo Horizonte"),
 *     @OA\Property(property="dataLibera", type="string", format="date", example="2023-04-01"),
 *     @OA\Property(property="quantidade", type="string", example="15.000"),
 *     @OA\Property(property="id_material", type="string", nullable=true, example="1", description="aju_unidade.id_unidade do legado."),
 *     @OA\Property(property="material", type="string", example="CESTA"),
 *     @OA\Property(property="evento", type="string", nullable=true, example="CHUVA"),
 *     @OA\Property(property="deposito", type="string", example="BELO HORIZONTE"),
 *     @OA\Property(property="status", type="integer", enum={0, 1}, example=1)
 * )
 */
final class LiberacaoApiController extends Controller
{
    public function __construct(private readonly LiberacaoApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/liberacoes",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Liberacoes agrupadas por ano",
     *     description="Paridade com o endpoint publico pubajudah do sistema legado, lendo do banco do NewSDC.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ano_comeco", in="query", required=true, description="Ano inicial, 4 digitos", @OA\Schema(type="integer", example=2022)),
     *     @OA\Parameter(name="ano_fim", in="query", required=false, description="Ano final, 4 digitos, no maximo o ano corrente", @OA\Schema(type="integer", example=2024)),
     *     @OA\Parameter(name="evento", in="query", required=false, @OA\Schema(type="string", enum={"AJUDA HUMANITARIA","CEDEC","CHUVA","COVID-19","OUTROS","SECA"})),
     *     @OA\Response(
     *         response=200,
     *         description="Liberacoes agrupadas por ano, com totais por situacao",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", description="Chave = ano", additionalProperties=@OA\Schema(type="array", @OA\Items(ref="#/components/schemas/AhLiberacaoItem"))),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="totais", type="object",
     *                     @OA\Property(property="total_registros", type="integer", example=3582),
     *                     @OA\Property(property="total_pagas", type="integer", example=3300),
     *                     @OA\Property(property="total_aberto", type="integer", example=200),
     *                     @OA\Property(property="total_canceladas", type="integer", example=82)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Parametros invalidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ConsultaLiberacaoApiRequest $request): JsonResponse
    {
        return response()->json($this->servico->agrupadasPorAno(
            $request->anoComeco(),
            $request->anoFim(),
            $request->evento(),
        ));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/liberacoes/cedec",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Liberacoes em formato plano, uma linha por item",
     *     description="Paridade com o endpoint publico pubajudahCedec do sistema legado. Considera itens de status 0 ou 1. Retorna lista vazia enquanto a carga de itens de liberacao do legado nao estiver concluida.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista plana de itens liberados",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AhLiberacaoCedecItem"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function cedec(): JsonResponse
    {
        return response()->json($this->servico->planaParaCedec());
    }
}
