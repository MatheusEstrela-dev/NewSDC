<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Requests\StoreEmpreendimentoRequest;
use App\Modules\Pae\Requests\UpdateEmpreendimentoRequest;
use App\Modules\Pae\Resources\EmpreendimentoResource;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="PAE",
 *     description="Endpoints do módulo PAE (Plano de Ação de Emergência)"
 * )
 *
 * @OA\Schema(
 *     schema="Empreendimento",
 *     type="object",
 *     title="Empreendimento PAE",
 *     description="Empreendimento (barragem) cadastrado no modulo PAE",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
 *     @OA\Property(property="status", type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"}, example="OPERACAO"),
 *     @OA\Property(property="mina", type="string", nullable=true, example="Mina Sul"),
 *     @OA\Property(property="metodo_construtivo", type="string", nullable=true, example="Alteamento a Jusante"),
 *     @OA\Property(property="material", type="string", nullable=true, example="Rejeitos"),
 *     @OA\Property(property="finalidade", type="string", nullable=true, example="Contencao de Rejeitos"),
 *     @OA\Property(property="volume", type="number", format="float", nullable=true, example=12500000.50),
 *     @OA\Property(property="populacao_zas", type="integer", nullable=true, example=1500),
 *     @OA\Property(property="orgao_fiscalizador", type="string", nullable=true, example="ANM"),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer", example=123),
 *         @OA\Property(property="nome", type="string", example="Itabirito"),
 *         @OA\Property(property="uf", type="string", example="MG")
 *     ),
 *     @OA\Property(property="empreendedor", type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="nome", type="string", example="Vale S/A"),
 *         @OA\Property(property="cnpj", type="string", example="33592510000154")
 *     ),
 *     @OA\Property(property="coordenador", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="telefone", type="string", nullable=true),
 *         @OA\Property(property="email", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="coordenador_substituto", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="telefone", type="string", nullable=true),
 *         @OA\Property(property="email", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="ultimo_protocolo", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=42),
 *         @OA\Property(property="num_protocolo", type="string", example="2024.10.15.0081"),
 *         @OA\Property(property="sigibar", type="string", example="SIG-001"),
 *         @OA\Property(property="status", type="string", example="analise"),
 *         @OA\Property(property="dt_entrada", type="string", format="date", example="2024-10-15"),
 *         @OA\Property(property="ccpae_vencimento", type="string", format="date", example="2025-10-15")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=75)
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     type="object",
 *     @OA\Property(property="first", type="string", example="/api/v1/pae/empreendimentos?page=1"),
 *     @OA\Property(property="last", type="string", example="/api/v1/pae/empreendimentos?page=5"),
 *     @OA\Property(property="prev", type="string", nullable=true),
 *     @OA\Property(property="next", type="string", example="/api/v1/pae/empreendimentos?page=2")
 * )
 */
class EmpreendimentoController extends Controller
{
    public function __construct(private readonly EmpreendimentoApiService $service)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pae/empreendimentos",
     *     summary="Lista todos os empreendimentos PAE",
     *     description="Retorna uma lista paginada de todos os empreendimentos cadastrados no sistema PAE",
     *     operationId="listEmpreendimentos",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numero da pagina",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por pagina",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="municipio_id",
     *         in="query",
     *         description="Filtrar por municipio",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Busca textual no nome do empreendimento",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de empreendimentos retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Empreendimento")),
     *             @OA\Property(property="meta", type="object", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", type="object", ref="#/components/schemas/PaginationLinks")
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
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->listPaginated(
            filters: $request->only(['municipio_id', 'status', 'search']),
            perPage: (int) $request->input('per_page', 15),
        );

        return EmpreendimentoResource::collection($paginator)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Exibe um empreendimento especifico",
     *     description="Retorna os detalhes completos de um empreendimento PAE",
     *     operationId="showEmpreendimento",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do empreendimento",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empreendimento encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Empreendimento")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empreendimento nao encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Empreendimento nao encontrado.")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $emp = $this->service->findById($id);

        return (new EmpreendimentoResource($emp))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pae/empreendimentos",
     *     summary="Cria um novo empreendimento PAE",
     *     description="Cadastra um novo empreendimento no sistema PAE",
     *     operationId="storeEmpreendimento",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nome","municipio_id","pae_empdor_id"},
     *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
     *             @OA\Property(property="status", type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"}),
     *             @OA\Property(property="municipio_id", type="integer", example=123),
     *             @OA\Property(property="pae_empdor_id", type="integer", example=5),
     *             @OA\Property(property="m_construcao", type="string", example="Alteamento a Jusante"),
     *             @OA\Property(property="material", type="string", example="Rejeitos"),
     *             @OA\Property(property="finalidade", type="string", example="Contencao de Rejeitos"),
     *             @OA\Property(property="volume", type="number", format="float", example=12500000.50),
     *             @OA\Property(property="pop_zas", type="integer", example=1500),
     *             @OA\Property(property="orgao_fisc", type="string", example="ANM"),
     *             @OA\Property(property="coordenador", type="string"),
     *             @OA\Property(property="tel_coordenador", type="string"),
     *             @OA\Property(property="email_coord", type="string", format="email"),
     *             @OA\Property(property="mina", type="string"),
     *             @OA\Property(property="coordenador_sub", type="string"),
     *             @OA\Property(property="tel_coordenador_sub", type="string"),
     *             @OA\Property(property="email_coord_sub", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Empreendimento criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Empreendimento"),
     *             @OA\Property(property="message", type="string", example="Empreendimento criado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validacao",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreEmpreendimentoRequest $request): JsonResponse
    {
        $emp = $this->service->create($request->validated());

        return (new EmpreendimentoResource($emp))
            ->additional(['message' => 'Empreendimento criado com sucesso'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Atualiza um empreendimento PAE",
     *     description="Atualiza os dados de um empreendimento existente. Todos os campos sao opcionais.",
     *     operationId="updateEmpreendimento",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do empreendimento",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Todos os campos sao opcionais.",
     *         @OA\JsonContent(
     *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
     *             @OA\Property(property="status", type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"}),
     *             @OA\Property(property="municipio_id", type="integer", example=123),
     *             @OA\Property(property="pae_empdor_id", type="integer", example=5),
     *             @OA\Property(property="m_construcao", type="string", example="Alteamento a Jusante"),
     *             @OA\Property(property="material", type="string", example="Rejeitos"),
     *             @OA\Property(property="finalidade", type="string", example="Contencao de Rejeitos"),
     *             @OA\Property(property="volume", type="number", format="float", example=12500000.50),
     *             @OA\Property(property="pop_zas", type="integer", example=1500),
     *             @OA\Property(property="orgao_fisc", type="string", example="ANM"),
     *             @OA\Property(property="coordenador", type="string"),
     *             @OA\Property(property="tel_coordenador", type="string"),
     *             @OA\Property(property="email_coord", type="string", format="email"),
     *             @OA\Property(property="mina", type="string"),
     *             @OA\Property(property="coordenador_sub", type="string"),
     *             @OA\Property(property="tel_coordenador_sub", type="string"),
     *             @OA\Property(property="email_coord_sub", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empreendimento atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Empreendimento"),
     *             @OA\Property(property="message", type="string", example="Empreendimento atualizado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empreendimento nao encontrado"
     *     )
     * )
     */
    public function update(UpdateEmpreendimentoRequest $request, int $id): JsonResponse
    {
        $emp = $this->service->findById($id);
        $emp = $this->service->update($emp, $request->validated());

        return (new EmpreendimentoResource($emp))
            ->additional(['message' => 'Empreendimento atualizado com sucesso'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Remove um empreendimento PAE",
     *     description="Remove um empreendimento do sistema (soft delete)",
     *     operationId="deleteEmpreendimento",
     *     tags={"PAE"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do empreendimento",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Empreendimento removido com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empreendimento nao encontrado"
     *     )
     * )
     */
    public function destroy(int $id): Response
    {
        $emp = $this->service->findById($id);
        $this->service->delete($emp);

        return response()->noContent();
    }
}
