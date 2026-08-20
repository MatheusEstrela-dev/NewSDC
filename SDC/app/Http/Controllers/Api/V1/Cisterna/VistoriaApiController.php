<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Api\V1\Cisterna\Requests\ListarVistoriasRequest;
use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Resources\VistoriaResource;
use App\Modules\Cisterna\Services\VistoriaService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Vistoria e recurso do modulo Cisterna, nao modulo proprio: a cadeia
 * fornecedor -> COMPDEC -> CEDEC pertence ao beneficiario, e a tabela e
 * `cisterna_vistorias`. Por isso o prefixo e /api/v1/cisternas/vistorias.
 */
class VistoriaApiController extends Controller
{
    public function __construct(
        private readonly VistoriaService $service,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/vistorias",
     *     summary="Lista vistorias das tres etapas",
     *     description="Uma linha por etapa do mesmo documento, com `unique (beneficiario_id, etapa)`. No legado eram tres tabelas separadas (sinc_cisterna_rel_fornecedor, _rel_compdec, _rel_cedec). ATENCAO: `numero_instalacao` e preenchido SOMENTE na etapa `fornecedor` — nas etapas `compdec` e `cedec` e sempre nulo, por contrato, nao por falta de dado. A chave `dados_administrativos` aparece somente na etapa `cedec`.",
     *     operationId="cisternasVistoriasIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="etapa", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="beneficiario_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comunidade_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="numero_instalacao", in="query", required=false, description="Numero do QR Code. Existe somente na etapa fornecedor.", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="concluida", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="data_relatorio_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_relatorio_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaVistoriaItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.vistorias.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarVistoriasRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaVistoria::class);

        $pagina = $this->service->listar(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
            $request->porPagina(),
        );

        return VistoriaResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/vistorias/{id}",
     *     summary="Detalhe da vistoria com o checklist conferido",
     *     description="Traz `itens`: uma entrada por item de instalacao conferido (13 itens no enum ItemInstalacao). No legado o checklist eram ~87 colunas espalhadas pelas tres tabelas, com nomes divergentes entre elas. O item `fixacao` traz as subquantidades (abracadeira, bucha, parafuso) em `detalhes`.",
     *     operationId="cisternasVistoriasShow",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=8088)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vistoria",
     *
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CisternaVistoriaItem"))
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Fora do territorio do usuario ou sem permissao", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Nao encontrada", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    /**
     * O parametro se chama `cisternaVistoria` porque routes/modules/tdap.php
     * registra Route::model() explicito para `{vistoria}`, e binder explicito
     * vence o implicito -- com o nome curto, o Laravel resolveria o model do
     * TDAP nesta rota.
     */
    public function show(CisternaVistoria $cisternaVistoria): VistoriaResource
    {
        $vistoria = $cisternaVistoria;

        // `beneficiario` carregado ANTES da policy: dentroDoTerritorio() le
        // $vistoria->beneficiario?->municipio_id, e sem o load isso dispara uma
        // consulta lazy a cada chamada.
        $vistoria->load([
            'beneficiario:id,nome,cpf,municipio_id',
            'beneficiario.municipio:id,nome,uf',
            'itensConferidos',
            'notificacoes',
            'media',
        ]);

        $this->authorize('view', $vistoria);

        return VistoriaResource::make($vistoria);
    }
}
