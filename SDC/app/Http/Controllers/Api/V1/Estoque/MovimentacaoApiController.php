<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Estoque;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\DTOs\EntradaEstoqueDTO;
use App\Modules\Estoque\DTOs\SaidaEstoqueDTO;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\Estoque\Services\MovimentacaoEstoqueService;
use App\Modules\Estoque\Support\RegrasEstoque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="EstoqueMovimentacao",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="data", type="string", format="date-time"),
 *     @OA\Property(property="tipo", type="string", enum={"entrada","saida"}),
 *     @OA\Property(property="produto", type="string"),
 *     @OA\Property(property="quantidade", type="integer"),
 *     @OA\Property(property="lote", type="string"),
 *     @OA\Property(property="almoxarifado", type="string"),
 *     @OA\Property(property="saldo_apos", type="integer"),
 *     @OA\Property(property="romaneio", type="string", nullable=true, example="ROM-2026-00012"),
 *     @OA\Property(property="motivo", type="string", description="Destino da saida ou fornecedor/doador da entrada")
 * )
 */
final class MovimentacaoApiController extends Controller
{
    use RespostaPadrao;

    public function __construct(
        private readonly EstoqueConsultaService $consulta,
        private readonly MovimentacaoEstoqueService $movimentacao,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/estoque/movimentacoes",
     *     tags={"Estoque"},
     *     summary="Extrato de movimentacoes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="tipo", in="query", @OA\Schema(type="string", enum={"entrada","saida"})),
     *     @OA\Parameter(name="produto_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="almoxarifado_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="romaneio", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Pagina de movimentacoes", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/EstoqueMovimentacao")),
     *         @OA\Property(property="next_page_url", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=403, description="Sem permissao estoque.movimentacoes.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->consulta->movimentacoes(
            $request->only(['tipo', 'produto_id', 'almoxarifado_id', 'romaneio']),
            min(100, max(1, (int) $request->integer('per_page', 20))),
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/estoque/movimentacoes",
     *     tags={"Estoque"},
     *     summary="Registra entrada (lote) ou saida (romaneio)",
     *     description="tipo=entrada: produto_id, almoxarifado_id, lote, validade, quantidade, fornecedor. tipo=saida: almoxarifado_id, destino_tipo (municipio|abrigo), destino_municipio_id ou destino_descricao, itens[{produto_id, quantidade}]. A saida baixa em FEFO e e recusada inteira se faltar saldo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"tipo"},
     *         @OA\Property(property="tipo", type="string", enum={"entrada","saida"}),
     *         @OA\Property(property="produto_id", type="integer"),
     *         @OA\Property(property="almoxarifado_id", type="integer"),
     *         @OA\Property(property="lote", type="string"),
     *         @OA\Property(property="validade", type="string", format="date"),
     *         @OA\Property(property="quantidade", type="integer"),
     *         @OA\Property(property="fornecedor", type="string"),
     *         @OA\Property(property="destino_tipo", type="string", enum={"municipio","abrigo"}),
     *         @OA\Property(property="destino_municipio_id", type="integer"),
     *         @OA\Property(property="destino_descricao", type="string"),
     *         @OA\Property(property="itens", type="array", @OA\Items(
     *             @OA\Property(property="produto_id", type="integer"),
     *             @OA\Property(property="quantidade", type="integer")
     *         ))
     *     )),
     *     @OA\Response(response=201, description="Movimentacao registrada", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=422, description="Dados invalidos ou saldo insuficiente", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao estoque.movimentacoes.create", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $tipo = $request->input('tipo');
        if (! in_array($tipo, ['entrada', 'saida'], true)) {
            return $this->erro('Dados invalidos.', 422, ['tipo' => ['Informe tipo entrada ou saida.']]);
        }

        $validador = Validator::make($request->all(), $tipo === 'entrada' ? RegrasEstoque::entrada() : RegrasEstoque::saida());
        if ($validador->fails()) {
            return $this->erro('Dados invalidos.', 422, $validador->errors()->toArray());
        }

        try {
            if ($tipo === 'entrada') {
                $mov = $this->movimentacao->entrada(EntradaEstoqueDTO::fromValidated($validador->validated()), $request->user()?->id);

                return $this->sucesso('Entrada registrada.', $this->consulta->linhaDeMovimentacao($mov->load(['produto', 'lote', 'almoxarifado'])), 201);
            }

            $saida = $this->movimentacao->saida(SaidaEstoqueDTO::fromValidated($validador->validated()), $request->user()?->id);

            return $this->sucesso("Saida registrada no romaneio {$saida['romaneio']}.", [
                'romaneio' => $saida['romaneio'],
                'itens'    => $saida['movimentacoes']->map(fn ($m) => $this->consulta->linhaDeMovimentacao($m->load(['produto', 'lote', 'almoxarifado', 'destinoMunicipio'])))->values(),
            ], 201);
        } catch (\DomainException $e) {
            return $this->erro($e->getMessage(), 422, ['estoque' => [$e->getMessage()]]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/estoque/saldos",
     *     tags={"Estoque"},
     *     summary="Saldo por lote (filtro por almoxarifado, categoria, validade)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="almoxarifado_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="categoria", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="validade", in="query", @OA\Schema(type="string", enum={"em_dia","vence_30","vencido"})),
     *     @OA\Response(response=200, description="Pagina de lotes com saldo"),
     *     @OA\Response(response=403, description="Sem permissao estoque.produtos.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function saldos(Request $request): JsonResponse
    {
        return response()->json($this->consulta->lotes(
            $request->only(['busca', 'categoria', 'almoxarifado_id', 'validade', 'status']),
            min(100, max(1, (int) $request->integer('per_page', 30))),
        ));
    }
}
