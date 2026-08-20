<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Api\V1\Cisterna\Requests\ListarBeneficiariosRequest;
use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Resources\BeneficiarioIndexResource;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\Cisterna\Services\BeneficiarioService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Tag(
 *     name="Cisternas",
 *     description="Projeto Cisterna — cadastro do beneficiario e a cadeia de vistoria em tres etapas (fornecedor, COMPDEC, CEDEC). Somente leitura. O recorte territorial vem do usuario dono do token."
 * )
 */
class BeneficiarioApiController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $service,
        private readonly BeneficiarioExportService $exportService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios",
     *     summary="Lista beneficiarios do Projeto Cisterna",
     *     description="Lista paginada. O recorte territorial vem do usuario dono do token: orgao COMPDEC ve somente o proprio municipio, e a role cisterna_fornecedor ve somente obras em envio_instalacao ou instalado. O recorte vence o filtro `municipio_id`. Atencao: 516 dos registros tem `situacao_analise = duplicado` e sao tombstone do legado, nao cadastro ativo — filtre-os em analise.",
     *     operationId="cisternasBeneficiariosIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1, example=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comunidade_id", in="query", required=false, description="Aceita lista separada por virgula.", @OA\Schema(type="string", example="12,34")),
     *     @OA\Parameter(name="situacao_analise", in="query", required=false, description="Aceita lista separada por virgula.", @OA\Schema(type="string", example="aprovado,ressalva")),
     *     @OA\Parameter(name="situacao_obra", in="query", required=false, @OA\Schema(type="string", example="instalado")),
     *     @OA\Parameter(name="lote_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="ordem_servico_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="cpf", in="query", required=false, description="Prefixo. Aceita com ou sem mascara.", @OA\Schema(type="string", example="123456789")),
     *     @OA\Parameter(name="search", in="query", required=false, description="Busca por nome, apoiada em indice GIN pg_trgm.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="atendido_por_pipa", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="numero_instalacao", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="etapa_concluida", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="etapa_pendente", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="ranqueamento", in="query", required=false, description="Quando verdadeiro, substitui a ordenacao pela ordem de ranqueamento.", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"nome","cpf","situacao_analise","situacao_obra","municipio","comunidade","etapas"})),
     *     @OA\Parameter(name="direction", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaBeneficiarioItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.beneficiarios.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarBeneficiariosRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaBeneficiario::class);

        $pagina = $this->service->listar(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
            $request->porPagina(),
        );

        return BeneficiarioIndexResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios/{id}",
     *     summary="Detalhe do beneficiario",
     *     description="Traz criterios sociais, avaliacao tecnica do telhado, atendimento por pipa, as vistorias das tres etapas com os itens conferidos, as notificacoes e a midia. Um usuario de orgao COMPDEC recebe 403 ao pedir beneficiario de outro municipio.",
     *     operationId="cisternasBeneficiariosShow",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=4201)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Beneficiario",
     *
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CisternaBeneficiarioDetail"))
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Fora do territorio do usuario ou sem permissao", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Nao encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(CisternaBeneficiario $beneficiario): BeneficiarioResource
    {
        // Policy por instancia, e nao middleware can:, porque o recorte
        // territorial do COMPDEC depende do municipio DESTE registro.
        $this->authorize('view', $beneficiario);

        $beneficiario->load([
            'municipio:id,nome,uf',
            'comunidade:id,nome',
            'ordemServico:id,nome,lote_id',
            'ordemServico.lote:id,nome',
            'vistorias.itensConferidos',
            'atendimentosPipa',
            'notificacoes',
            'media',
        ]);

        return BeneficiarioResource::make($beneficiario);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios/export",
     *     summary="Exporta beneficiarios em CSV",
     *     description="CSV streamado, sem teto de linhas — aceita os mesmos filtros do index e aplica o mesmo recorte de perfil. Exige a permissao cisternas.beneficiarios.export, separada da de leitura.",
     *     operationId="cisternasBeneficiariosExport",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="situacao_analise", in="query", required=false, @OA\Schema(type="string", example="aprovado")),
     *     @OA\Parameter(name="situacao_obra", in="query", required=false, @OA\Schema(type="string", example="instalado")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo CSV",
     *
     *         @OA\MediaType(mediaType="text/csv", @OA\Schema(type="string", format="binary"))
     *     ),
     *
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.beneficiarios.export", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function export(ListarBeneficiariosRequest $request): StreamedResponse
    {
        $this->authorize('export', CisternaBeneficiario::class);

        return $this->exportService->streamCsv(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
        );
    }
}
