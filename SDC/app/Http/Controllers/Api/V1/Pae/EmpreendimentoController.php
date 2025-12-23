<?php

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Models\Empreendimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
 *     @OA\Property(property="tipo", type="string", example="Barragem de Rejeitos"),
 *     @OA\Property(
 *         property="municipio",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=123),
 *         @OA\Property(property="nome", type="string", example="Itabirito"),
 *         @OA\Property(property="uf", type="string", example="MG")
 *     ),
 *     @OA\Property(
 *         property="coordenadas",
 *         type="object",
 *         @OA\Property(property="lat", type="number", format="float", example=-20.2547),
 *         @OA\Property(property="lng", type="number", format="float", example=-43.8011)
 *     ),
 *     @OA\Property(property="protocolo", type="string", example="2024.10.15.0081"),
 *     @OA\Property(property="status", type="string", enum={"aprovado", "em_analise", "pendente", "vencido"}, example="aprovado"),
 *     @OA\Property(property="nivel_emergencia", type="integer", enum={1, 2, 3}, example=1),
 *     @OA\Property(property="data_emissao", type="string", format="date", example="2024-10-15"),
 *     @OA\Property(property="proximo_vencimento", type="string", format="date", example="2025-10-15"),
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
    public function __construct()
    {
        $this->authorizeResource(Empreendimento::class, 'empreendimento');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pae/empreendimentos",
     *     summary="Lista todos os empreendimentos PAE",
     *     description="Retorna uma lista paginada de todos os empreendimentos cadastrados no sistema PAE",
     *     operationId="listEmpreendimentos",
     *     tags={"PAE"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="municipio_id",
     *         in="query",
     *         description="Filtrar por município",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"aprovado", "em_analise", "pendente", "vencido"})
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
     *         description="Não autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Implementar lógica real
        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'total' => 0,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Exibe um empreendimento específico",
     *     description="Retorna os detalhes completos de um empreendimento PAE, incluindo documentos, histórico e comitê",
     *     operationId="showEmpreendimento",
     *     tags={"PAE"},
     *     security={{"sanctum": {}}},
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
     *         description="Empreendimento não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Empreendimento não encontrado.")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        // TODO: Implementar lógica real
        return response()->json([
            'data' => [
                'id' => $id,
                'nome' => 'Barragem Sul Superior',
                'tipo' => 'Barragem de Rejeitos',
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pae/empreendimentos",
     *     summary="Cria um novo empreendimento PAE",
     *     description="Cadastra um novo empreendimento no sistema PAE",
     *     operationId="storeEmpreendimento",
     *     tags={"PAE"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nome", "tipo", "municipio_id", "latitude", "longitude", "nivel_emergencia", "status"},
     *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
     *             @OA\Property(property="tipo", type="string", enum={"Barragem de Rejeitos", "Barragem de Água", "Outro"}, example="Barragem de Rejeitos"),
     *             @OA\Property(property="municipio_id", type="integer", example=123),
     *             @OA\Property(property="latitude", type="number", format="float", example=-20.2547),
     *             @OA\Property(property="longitude", type="number", format="float", example=-43.8011),
     *             @OA\Property(property="nivel_emergencia", type="integer", enum={1, 2, 3}, example=1),
     *             @OA\Property(property="status", type="string", enum={"aprovado", "em_analise", "pendente", "vencido"}, example="em_analise"),
     *             @OA\Property(property="data_emissao", type="string", format="date", example="2024-10-15"),
     *             @OA\Property(property="proximo_vencimento", type="string", format="date", example="2025-10-15")
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
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // TODO: Implementar lógica real
        return response()->json([
            'data' => $request->all(),
            'message' => 'Empreendimento criado com sucesso',
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Atualiza um empreendimento PAE",
     *     description="Atualiza os dados de um empreendimento existente",
     *     operationId="updateEmpreendimento",
     *     tags={"PAE"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do empreendimento",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
     *             @OA\Property(property="status", type="string", enum={"aprovado", "em_analise", "pendente", "vencido"}),
     *             @OA\Property(property="nivel_emergencia", type="integer", enum={1, 2, 3})
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
     *         description="Empreendimento não encontrado"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // TODO: Implementar lógica real
        return response()->json([
            'data' => array_merge(['id' => $id], $request->all()),
            'message' => 'Empreendimento atualizado com sucesso',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/pae/empreendimentos/{id}",
     *     summary="Remove um empreendimento PAE",
     *     description="Remove um empreendimento do sistema",
     *     operationId="deleteEmpreendimento",
     *     tags={"PAE"},
     *     security={{"sanctum": {}}},
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
     *         description="Empreendimento não encontrado"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        // TODO: Implementar lógica real
        return response()->json(null, 204);
    }

    /**
     * Aprova um empreendimento (ação customizada)
     */
    public function approve(Request $request, int $empreendimento): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Empreendimento aprovado com sucesso',
            'data' => [
                'id' => $empreendimento,
                'approved_by' => $request->user()?->id,
                'approved_at' => now()->toIso8601String(),
            ],
        ], 200);
    }
}

