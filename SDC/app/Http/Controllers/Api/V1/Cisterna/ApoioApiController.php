<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Api\V1\Cisterna\Requests\ListarComunidadesRequest;
use App\Http\Controllers\Api\V1\Cisterna\Requests\ListarPaginadoRequest;
use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Resources\ComunidadeResource;
use App\Modules\Cisterna\Resources\LoteResource;
use App\Modules\Cisterna\Resources\OrdemServicoResource;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\LoteService;
use App\Modules\Cisterna\Services\OrdemServicoService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * As tres listagens de referencia do modulo: comunidade, lote e ordem de
 * servico. Juntas num controller porque nenhuma tem regra propria alem de
 * paginar -- tres arquivos de trinta linhas nao ajudariam ninguem.
 */
class ApoioApiController extends Controller
{
    // Sufixo `Service` nas propriedades de proposito: sem ele, `$this->lotes`
    // (propriedade) e `$this->lotes()` (metodo) leem como a mesma coisa e nao
    // sao -- PHP aceita, quem revisa tropeca.
    public function __construct(
        private readonly ComunidadeService $comunidadeService,
        private readonly LoteService $loteService,
        private readonly OrdemServicoService $ordemServicoService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/comunidades",
     *     summary="Lista comunidades atendidas",
     *     description="840 comunidades em 55 municipios. `beneficiarios` e a contagem por `comunidade_id`, nao por nome: no legado o join era por nome de comunidade sem o municipio, e os nomes que existem em mais de um municipio somavam a contagem entre eles. Recorte territorial: orgao COMPDEC ve somente o proprio municipio.",
     *     operationId="cisternasComunidadesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="apenas_ativas", in="query", required=false, @OA\Schema(type="boolean")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaComunidadeItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.comunidades.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function comunidades(ListarComunidadesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        $pagina = $this->comunidadeService->listar(
            $request->filtros(),
            $request->porPagina(),
            PerfilCisterna::deUsuario($request->user()),
        );

        return ComunidadeResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/lotes",
     *     summary="Lista lotes de contratacao",
     *     description="SEM recorte territorial, por contrato: `cisterna_lotes` nao tem `municipio_id` — o lote e nacional, e um COMPDEC precisa ve-lo para saber em que lote esta a propria ordem de servico. `ordens_servico` e a contagem de OS do lote.",
     *     operationId="cisternasLotesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaLoteItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.lotes.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function lotes(ListarPaginadoRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaLote::class);

        return LoteResource::collection($this->loteService->listar($request->porPagina()));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/ordens-servico",
     *     summary="Lista ordens de servico",
     *     description="SEM recorte territorial, mesma razao do lote. `documento_url` e a URL do processo no SEI vinda do legado; `documento_anexo` e arquivo anexado no NewSDC, que o legado nao tinha.",
     *     operationId="cisternasOrdensServicoIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="lote_id", in="query", required=false, @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaOrdemServicoItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.ordens-servico.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function ordensServico(ListarPaginadoRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        $loteId = $request->filled('lote_id') ? $request->integer('lote_id') : null;

        return OrdemServicoResource::collection(
            $this->ordemServicoService->listar($loteId, $request->porPagina())
        );
    }
}
