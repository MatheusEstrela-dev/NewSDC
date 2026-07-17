<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pae\PaeNotificacaoResource;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\EmitirNotificacaoRequest;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="PaeNotificacao",
 *     type="object",
 *     title="Notificacao PAE",
 *     description="Ciclo de notificacao (1/2/3) emitido para um protocolo PAE",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="num_sei", type="string", example="SEI-1234.567890/2026-01"),
 *     @OA\Property(property="dt_notificacao", type="string", format="date", example="2026-07-17"),
 *     @OA\Property(property="prazo_final", type="string", format="date", example="2026-08-16"),
 *     @OA\Property(property="dt_devolutiva", type="string", format="date", nullable=true, example=null),
 *     @OA\Property(property="vencida", type="boolean", example=false),
 *     @OA\Property(property="obs", type="string", nullable=true, example=null)
 * )
 */
class NotificacaoController extends Controller
{
    public function __construct(
        private readonly PaeNotificacaoService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/pae/protocolos/{paeProtocolo}/notificacoes",
     *     summary="Lista os ciclos de notificacao de um protocolo PAE",
     *     description="Retorna os ciclos de notificacao (1/2/3) emitidos para o protocolo, com prazo e situacao de vencimento",
     *     operationId="listPaeNotificacoes",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paeProtocolo",
     *         in="path",
     *         description="ID do protocolo PAE",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notificacoes retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PaeNotificacao"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nao autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json([
            'data' => $this->service->listarPorProtocolo($paeProtocolo),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pae/protocolos/{paeProtocolo}/notificacoes",
     *     summary="Emite um novo ciclo de notificacao para o protocolo",
     *     description="Cria a analise (se ainda nao existir) e emite a proxima notificacao (ciclo 1/2/3), enviando e-mail ao coordenador do empreendimento quando cadastrado",
     *     operationId="storePaeNotificacao",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paeProtocolo",
     *         in="path",
     *         description="ID do protocolo PAE",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"num_sei"},
     *             @OA\Property(property="num_sei", type="string", example="SEI-1234.567890/2026-01"),
     *             @OA\Property(property="obs", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notificacao emitida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PaeNotificacao")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validacao (sem analista delegado, ciclo em aberto ou limite de 3 ciclos atingido)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(EmitirNotificacaoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        $notificacao = $this->service->emitir($paeProtocolo, $request->user(), $request->validated());

        return response()->json([
            'data' => new PaeNotificacaoResource($notificacao),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pae/notificacoes/{paeNotificacao}/devolutiva",
     *     summary="Registra a devolutiva de um ciclo de notificacao",
     *     description="Fecha o ciclo de notificacao informando a data de devolutiva recebida",
     *     operationId="storePaeNotificacaoDevolutiva",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paeNotificacao",
     *         in="path",
     *         description="ID da notificacao",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dt_devolutiva"},
     *             @OA\Property(property="dt_devolutiva", type="string", format="date", example="2026-07-17")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Devolutiva registrada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PaeNotificacao")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ciclo ja possui devolutiva registrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function devolutiva(RegistrarDevolutivaRequest $request, PaeNotificacao $paeNotificacao): JsonResponse
    {
        $notificacao = $this->service->registrarDevolutiva(
            $paeNotificacao,
            $request->user(),
            $request->validated()['dt_devolutiva']
        );

        return response()->json([
            'data' => new PaeNotificacaoResource($notificacao),
        ]);
    }
}
