<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Api\V1\Cisterna\Requests\ListarNotificacoesRequest;
use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Resources\NotificacaoResource;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacaoApiController extends Controller
{
    public function __construct(
        private readonly NotificacaoFiscalizacaoService $service,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/notificacoes",
     *     summary="Lista notificacoes de fiscalizacao",
     *     description="Notificacao polimorfica: o `notificavel` e um beneficiario ou uma vistoria, identificado pelo alias curto (`beneficiario` / `vistoria`), nao pelo FQCN. O recorte territorial cobre as duas pontas — o beneficiario tem `municipio_id`, e a vistoria chega ao municipio pela relacao. As 7 linhas migradas do legado sao dado de teste.",
     *     operationId="cisternasNotificacoesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="notificavel_type", in="query", required=false, @OA\Schema(type="string", enum={"beneficiario","vistoria"})),
     *     @OA\Parameter(name="notificavel_id", in="query", required=false, description="Obrigatorio quando notificavel_type e informado.", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="apenas_pendentes", in="query", required=false, description="Somente as ainda nao respondidas.", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaNotificacaoItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.notificacoes.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarNotificacoesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaNotificacao::class);

        $pagina = $this->service->listar(
            $request->filtros(),
            $request->porPagina(),
            PerfilCisterna::deUsuario($request->user()),
        );

        return NotificacaoResource::collection($pagina);
    }
}
