<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Decretacoes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AsynchronousResponse;
use App\Http\Traits\ApiResponseTrait;
use App\Jobs\Decretacoes\ExportPowerBIJob;
use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Requests\ReceiveProcessoRequest;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para o modulo de Decretacoes.
 *
 * FLUXO: Request -> Controller -> Service -> JSON
 *
 * RESPONSABILIDADES:
 * - Expor dados de Processos via API REST
 * - Receber dados externos (FIDE, Hexagon) via POST
 * - Exportar dados normalizados para Power BI
 *
 * @OA\Tag(
 *     name="Decretacoes",
 *     description="Endpoints do modulo de Decretacoes — listagem, detalhe, export BI e recebimento externo"
 * )
 */
class DecretacoesApiController extends Controller
{
    use ApiResponseTrait;
    use AsynchronousResponse;

    public function __construct(
        private readonly ProcessoQueryService $queryService,
        private readonly EntradaProcessoService $entradaService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/decretacoes",
     *     summary="Lista processos de decretacoes",
     *     operationId="decretacoesIndex",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reconhecimento", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de processos",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoList")
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'data_entrada_inicio', 'data_entrada_fim',
            'processo', 'reconhecimento', 'analista', 'situacao_anormalidade',
            'data_decreto_inicio', 'data_decreto_fim', 'vigencia_status',
            'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $data = $this->queryService->listForApiFlat($filters, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/decretacoes/{id}",
     *     summary="Detalhe de um processo de decretacao",
     *     operationId="decretacoesShow",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Dados completos do processo",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoItem")
     *     ),
     *     @OA\Response(response=404, description="Processo nao encontrado"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->queryService->showForApi($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Processo nao encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/decretacoes/export/power-bi",
     *     summary="Exporta dados normalizados para Power BI",
     *     operationId="decretacoesExportPowerBI",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="include_deleted", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Dados normalizados para BI",
     *         @OA\JsonContent(ref="#/components/schemas/DecretacaoPowerBIExport")
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function exportPowerBI(Request $request): JsonResponse
    {
        $data = $this->queryService->exportAllForApiFlat($request);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Versao assincrona do export Power BI. Despacha job e retorna 202 com
     * trace_id. Cliente consulta GET /api/v1/traces/{traceId} e baixa CSV
     * via /download quando completed.
     *
     * @OA\Get(
     *     path="/api/v1/decretacoes/export/power-bi/async",
     *     summary="Export Power BI assincrono (recomendado para datasets grandes)",
     *     operationId="decretacoesExportPowerBIAsync",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=202, description="Job enfileirado; consulte status via trace_id"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function exportPowerBIAsync(Request $request): JsonResponse
    {
        return $this->dispatchAsyncJob(
            jobClass: ExportPowerBIJob::class,
            type: 'export_decretacoes_powerbi',
            args: [$request->query()],
            meta: ['filters' => $request->query()],
            queue: 'bulk',
            estimatedSeconds: 120,
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/decretacoes/receive",
     *     summary="Recebe dados externos de decretacao",
     *     operationId="decretacoesReceive",
     *     tags={"Decretacoes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReceiveProcessoRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Processo criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ProcessoDecretacaoItem")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados invalidos"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function receive(ReceiveProcessoRequest $request): JsonResponse
    {
        $dto = ProcessoRequestDTO::fromRequest($request);
        $processo = $this->entradaService->createProcesso($dto);

        $data = $this->queryService->showForApiFlat($processo->id);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 201);
    }
}
