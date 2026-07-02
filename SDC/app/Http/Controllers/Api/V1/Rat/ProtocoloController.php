<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Http\Requests\ReceiveRatBIRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatExportBIService;
use App\Modules\Rat\Services\RatQueryService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RAT",
 *     description="Relatório de Atividade Técnica — listagem paginada, detalhe, exportação Power BI e recebimento externo"
 * )
 */
class ProtocoloController extends Controller
{
    public function __construct(
        private readonly RatWriteService   $writeService,
        private readonly RatQueryService   $queryService,
        private readonly RatExportBIService $exportService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/rat/protocolos",
     *     operationId="ratProtocolosIndex",
     *     tags={"RAT"},
     *     summary="Listar RATs paginado (Power BI / BI)",
     *     description="Retorna a lista paginada de Relatórios de Atividade Técnica com suporte a filtros de data, status, município e protocolo. Projetado para consumo pelo Power BI via Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="protocolo", in="query", required=false, description="Busca parcial no número do BOS (ex: 2026-000000001)", @OA\Schema(type="string", example="2026-000000001")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Filtrar por status do RAT", @OA\Schema(type="string", enum={"em_andamento","finalizado"}, example="finalizado")),
     *     @OA\Parameter(name="ano", in="query", required=false, description="Ano de criação do RAT (YYYY)", @OA\Schema(type="integer", example=2026)),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, description="Data mínima de criação (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2026-01-01")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, description="Data máxima de criação (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2026-12-31")),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Itens por página (padrão: 15, máx: 200)", @OA\Schema(type="integer", example=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Listagem paginada retornada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/RatProtocoloList")
     *     ),
     *     @OA\Response(response=401, description="Não autenticado — Bearer token ausente ou inválido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit atingido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage   = min((int) $request->input('per_page', 15), 200);
        $query     = $this->queryService->applyFilters($request);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => RatListResource::collection($paginator)->resolve(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat/protocolos/{id}",
     *     operationId="ratProtocolosShow",
     *     tags={"RAT"},
     *     summary="Detalhe completo de um RAT",
     *     description="Retorna todos os dados de um RAT pelo UUID: dados gerais, comunicação, local, endereço, recursos (com agentes), envolvidos, vistoria, histórico, anexos e RATs relacionados.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID do RAT",
     *         @OA\Schema(type="string", format="uuid", example="018f2a3b-0000-7000-8000-000000000001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhe completo do RAT",
     *         @OA\JsonContent(ref="#/components/schemas/RatProtocoloDetail")
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="RAT não encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(string $id): JsonResponse
    {
        $rat = $this->writeService->findById($id);

        if (!$rat) {
            return response()->json([
                'success' => false,
                'message' => 'Protocolo RAT nao encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/rat/protocolos",
     *     operationId="ratProtocolosReceive",
     *     tags={"RAT"},
     *     summary="Criar RAT via sistema externo",
     *     description="Cria um novo RAT a partir de dados enviados por sistema externo (Power BI, TDAP, integração). O campo `finalize` controla se o RAT é salvo como rascunho (`false`, padrão) ou já finalizado (`true`). Ao finalizar, `dados_gerais.data_fato`, `dados_gerais.nat_ocorrencia`, `local.uf` e `local.municipio_id` tornam-se obrigatórios.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReceiveRatProtocoloRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="RAT criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/RatProtocoloDetail")
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Dados inválidos ou campos obrigatórios ausentes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function receive(ReceiveRatBIRequest $request): JsonResponse
    {
        $rat = $this->writeService->createWithData([
            'dadosGerais' => $request->input('dados_gerais', []),
            'comunicacao' => $request->input('comunicacao', []),
            'local'       => $request->input('local', []),
            'endereco'    => $request->input('endereco', []),
            'recursos'    => $request->input('recursos', []),
            'envolvidos'  => $request->input('envolvidos', []),
            'vistoria'    => $request->input('vistoria', []),
            'finalize'    => (bool) $request->input('finalize', false),
        ]);

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat/protocolos/export/power-bi",
     *     operationId="ratProtocolosExportBI",
     *     tags={"RAT"},
     *     summary="Exportar RATs — formato nested Power BI (PAE)",
     *     description="Retorna TODOS os RATs (sem paginação) em formato nested compatível com o Power BI e o contrato PAE. Cada item contém os objetos: `dados_gerais`, `recursos[]`, `envolvidos[]`, `vistoria` e `historico`. Datas retornadas no formato `Y-m-d H:i:s`. Suporta os mesmos filtros do endpoint de listagem.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="protocolo", in="query", required=false, description="Busca parcial no número do BOS", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Filtrar por status", @OA\Schema(type="string", enum={"em_andamento","finalizado"})),
     *     @OA\Parameter(name="ano", in="query", required=false, description="Ano de criação (YYYY)", @OA\Schema(type="integer", example=2025)),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, description="Data mínima (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2025-01-01")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, description="Data máxima (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2025-12-31")),
     *     @OA\Response(
     *         response=200,
     *         description="Exportação nested retornada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/RatBIExportResponse")
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit atingido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function exportPowerBI(Request $request): JsonResponse
    {
        $dados = $this->exportService->getNormalizedDataForPowerBI($request);

        return response()->json([
            'sucesso' => true,
            'dados'   => $dados,
            'meta'    => [
                'total_registros'  => count($dados),
                'gerado_em'        => now()->toISOString(),
                'filtros_aplicados' => $this->queryService->getAppliedFiltersSummary($request),
            ],
        ]);
    }
}
