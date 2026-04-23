<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\DTOs\RatReceiveBIDTO;
use App\Modules\Rat\Http\Requests\ReceiveRatBIRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Services\RatExportBIService;
use App\Modules\Rat\Services\RatReceiveBIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para o modulo RAT.
 *
 * FLUXO: Request -> Controller -> Service -> JSON
 *
 * RESPONSABILIDADES:
 * - GET /api/v1/rat/protocolos                → listagem paginada
 * - GET /api/v1/rat/protocolos?format=powerbi → flat array para Power BI
 * - GET /api/v1/rat/protocolos/{id}           → detalhe completo
 * - POST /api/v1/rat/protocolos               → recebimento de dados externos
 *
 * @OA\Tag(
 *     name="RAT",
 *     description="Endpoints do modulo RAT (Registro de Atendimento Tecnico)"
 * )
 */
class ProtocoloController extends Controller
{
    public function __construct(
        private readonly RatExportBIService  $exportService,
        private readonly RatReceiveBIService $receiveService,
    ) {}

    /**
     * Lista protocolos RAT ou exporta flat para Power BI.
     *
     * ?format=powerbi → retorna array flat desnormalizado (sem paginacao)
     * sem parametro   → retorna lista paginada com RatListResource
     *
     * @OA\Get(
     *     path="/api/v1/rat/protocolos",
     *     summary="Lista protocolos RAT / export Power BI",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="format", in="query", required=false, @OA\Schema(type="string", enum={"powerbi"})),
     *     @OA\Parameter(name="protocolo", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="ano", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, description="Filtro por data inicial (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, description="Filtro por data final (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse")
     *             },
     *             example={
     *                 "success": true,
     *                 "data": {
     *                     {
     *                         "dados_gerais": {
     *                             "id": 155,
     *                             "numero_bos": "2025-000000001-001",
     *                             "status": 1,
     *                             "status_descricao": "Finalizado",
     *                             "uf": "MG",
     *                             "municipio": "620 - BELO HORIZONTE",
     *                             "data_fato": "2025-12-23 08:53:00",
     *                             "data_inicio_atividade": "2025-12-23 08:53:00",
     *                             "data_termino_atividade": "2025-12-23 08:53:00",
     *                             "comunicacao_data": "2025-12-23 08:53:00",
     *                             "created_at": "2025-12-23 08:53:14",
     *                             "updated_at": "2025-12-23 08:54:53"
     *                         },
     *                         "recursos": {
     *                             {
     *                                 "id": 8,
     *                                 "tipo_recurso": "aquatico",
     *                                 "categoria": "comunicacao",
     *                                 "numero_viatura": "HNH 1932",
     *                                 "placa": "HNH 1932",
     *                                 "orgao": "samu",
     *                                 "descricao": "Voluptatem rerum qui"
     *                             }
     *                         },
     *                         "envolvidos": {
     *                             {
     *                                 "id": 37,
     *                                 "tipo_pessoa": "juridica",
     *                                 "nome": "Quod ipsum omnis ir",
     *                                 "email": "buta@mailinator.com",
     *                                 "data_nascimento": "2017-09-23"
     *                             }
     *                         },
     *                         "vistoria": {
     *                             "id": 9,
     *                             "solicitante": {
     *                                 "nome": "MATHEUS KEVIN ESTRELA DA SILVA",
     *                                 "cpf": "312.312.312-38",
     *                                 "telefone": "(23) 13123-1231"
     *                             },
     *                             "imovel": {
     *                                 "tipo_imovel": "comercial",
     *                                 "tipo_construcao": "outro",
     *                                 "tipo_edificacao": "construcoes_area_risco",
     *                                 "numero_pavimentos": 9,
     *                                 "estado_conservacao": "pessimo",
     *                                 "regime_ocupacao": "proprio"
     *                             }
     *                         }
     *                     }
     *                 },
     *                 "meta": {
     *                     "current_page": 1,
     *                     "per_page": 15,
     *                     "total": 1,
     *                     "last_page": 1
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(response=400, description="Requisicao invalida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Erro de validacao", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit excedido")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = RatFilterDTO::fromArray($request->only([
            'protocolo', 'status', 'municipio', 'ano',
            'data_inicio', 'data_fim', 'per_page',
        ]));

        if ($request->input('format') === 'powerbi') {
            $data = $this->exportService->listForPowerBI($filters);

            return response()->json([
                'success' => true,
                'data'    => $data,
                'meta'    => [
                    'total_registros' => count($data),
                    'gerado_em'       => now()->toIso8601String(),
                ],
            ]);
        }

        $paginator = $this->exportService->listForApi($filters);

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
     * Detalhe completo de um RAT por UUID.
     *
     * @OA\Get(
     *     path="/api/v1/rat/protocolos/{id}",
     *     summary="Detalhe de um protocolo RAT",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Protocolo encontrado"),
     *     @OA\Response(response=404, description="Nao encontrado"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $rat = $this->exportService->findById($id);

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
     * Recebe dados externos e cria um novo RAT.
     *
     * @OA\Post(
     *     path="/api/v1/rat/protocolos",
     *     summary="Recebe dados externos e cria protocolo RAT",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="dados_gerais", type="object"),
     *             @OA\Property(property="comunicacao", type="object"),
     *             @OA\Property(property="local", type="object"),
     *             @OA\Property(property="endereco", type="object"),
     *             @OA\Property(property="recursos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="envolvidos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="vistoria", type="object"),
     *             @OA\Property(property="finalize", type="boolean", nullable=true, example=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="RAT criado com sucesso", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=400, description="Requisicao invalida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Dados invalidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit excedido")
     * )
     */
    public function receive(ReceiveRatBIRequest $request): JsonResponse
    {
        $dto = RatReceiveBIDTO::fromRequest($request);
        $rat = $this->receiveService->receive($dto);

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ], 201);
    }
}
