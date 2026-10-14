<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Estoque;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\DTOs\ProdutoDTO;
use App\Modules\Estoque\Services\CadastroEstoqueService;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\Estoque\Support\RegrasEstoque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Estoque", description="Inventario de donativos e insumos (#32) e movimentacoes de estoque (#33)")
 *
 * @OA\Schema(
 *     schema="EstoqueProduto",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="sku", type="string", example="CESTA-01"),
 *     @OA\Property(property="nome", type="string", example="Cesta basica"),
 *     @OA\Property(property="categoria", type="string", example="alimentos"),
 *     @OA\Property(property="categoria_label", type="string", example="Alimentos"),
 *     @OA\Property(property="unidade_base", type="string", example="un"),
 *     @OA\Property(property="patrimonio", type="string", nullable=true),
 *     @OA\Property(property="estoque_minimo", type="integer", example=50),
 *     @OA\Property(property="controla_validade", type="boolean"),
 *     @OA\Property(property="ativo", type="boolean"),
 *     @OA\Property(property="saldo_total", type="integer", example=320),
 *     @OA\Property(property="saldo_disponivel", type="integer", example=300, description="Sem lotes vencidos ou em quarentena"),
 *     @OA\Property(property="estoque_baixo", type="boolean")
 * )
 */
final class InventarioApiController extends Controller
{
    use RespostaPadrao;

    public function __construct(
        private readonly EstoqueConsultaService $consulta,
        private readonly CadastroEstoqueService $cadastro,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/inventario",
     *     tags={"Estoque"},
     *     summary="Inventario por produto, com saldo",
     *     description="Pagina simples (use links.next para o scroll infinito). Busca por nome, SKU ou patrimonio.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="busca", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoria", in="query", @OA\Schema(type="string", enum={"alimentos","higiene","dormitorio","limpeza","saude","vestuario","outros"})),
     *     @OA\Parameter(name="patrimonio", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="almoxarifado_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Pagina de produtos", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/EstoqueProduto")),
     *         @OA\Property(property="next_page_url", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao estoque.produtos.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $porPagina = min(100, max(1, (int) $request->integer('per_page', 30)));

        return response()->json($this->consulta->produtos(
            $request->only(['busca', 'categoria', 'patrimonio', 'almoxarifado_id']),
            $porPagina,
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventario",
     *     tags={"Estoque"},
     *     summary="Cadastra um produto no inventario",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"sku","nome","categoria"},
     *         @OA\Property(property="sku", type="string"),
     *         @OA\Property(property="nome", type="string"),
     *         @OA\Property(property="categoria", type="string"),
     *         @OA\Property(property="unidade_base", type="string"),
     *         @OA\Property(property="patrimonio", type="string"),
     *         @OA\Property(property="estoque_minimo", type="integer"),
     *         @OA\Property(property="controla_validade", type="boolean")
     *     )),
     *     @OA\Response(response=201, description="Produto criado", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=422, description="Dados invalidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao estoque.produtos.create", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validador = Validator::make($request->all(), RegrasEstoque::produto());
        if ($validador->fails()) {
            return $this->erro('Dados invalidos.', 422, $validador->errors()->toArray());
        }

        $produto = $this->cadastro->criarProduto(ProdutoDTO::fromValidated($validador->validated()));

        return $this->sucesso('Produto cadastrado.', $this->consulta->linhaDeProduto($produto), 201);
    }
}
