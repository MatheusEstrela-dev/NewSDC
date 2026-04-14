<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\BI;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Services\ProcessoExportService;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="BI - Entrada",
 *     description="Endpoints para acesso aos dados de entrada de desastres e eventos para Power BI"
 * )
 */
class EntradaController extends Controller
{
    public function __construct(
        private readonly ProcessoExportService $exportService,
        private readonly ProcessoQueryService $queryService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bi/entrada",
     *     summary="Lista entradas de processos normalizadas para Power BI",
     *     description="Retorna lista flat de processos de decretacoes com dados de municipios e totais de desastres, otimizada para consumo pelo Power BI",
     *     operationId="biEntradaIndex",
     *     tags={"BI - Entrada"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="data_entrada_inicio", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-01-01")),
     *     @OA\Parameter(name="data_entrada_fim", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-12-31")),
     *     @OA\Parameter(name="processo", in="query", required=false, @OA\Schema(type="string", enum={"MUNICIPAL", "ESTADUAL"})),
     *     @OA\Parameter(name="reconhecimento", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="analista", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="situacao_anormalidade", in="query", required=false, @OA\Schema(type="string", enum={"SE", "ECP"})),
     *     @OA\Parameter(name="data_decreto_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_decreto_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="vigencia_status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="tipo_desastre_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="n_protocolo_fide", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="include_deleted", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista normalizada retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="sucesso", type="boolean", example=true),
     *             @OA\Property(property="dados", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=98),
     *                 @OA\Property(property="uf", type="string", example="MG"),
     *                 @OA\Property(property="municipio", type="string", example="Juiz de Fora"),
     *                 @OA\Property(property="codigo_ibge", type="string", example="3136702"),
     *                 @OA\Property(property="macroregiao", type="string", example="ZONA DA MATA"),
     *                 @OA\Property(property="latitude_dec", type="number", format="float", example=-21.764167),
     *                 @OA\Property(property="longitude_dec", type="number", format="float", example=-43.350278),
     *                 @OA\Property(property="data_registro", type="string", format="date", example="2025-11-19"),
     *                 @OA\Property(property="data_criacao", type="string", format="date-time"),
     *                 @OA\Property(property="deletado", type="boolean", example=false),
     *                 @OA\Property(property="protocolo", type="string", example="MG-F-3136702-13321-20250613"),
     *                 @OA\Property(property="cobrade", type="string", example="1.3.3.2.1"),
     *                 @OA\Property(property="tipo_desastre", type="string", example="Onda de frio: friagem."),
     *                 @OA\Property(property="status", type="string", example="Reconhecido pelo Estado e pela Uniao"),
     *                 @OA\Property(property="data_fato", type="string", format="date"),
     *                 @OA\Property(property="data_decreto_municipal", type="string", format="date", nullable=true),
     *                 @OA\Property(property="data_publicacao_mg", type="string", format="date", nullable=true),
     *                 @OA\Property(property="prazo_vigencia_dias", type="integer", nullable=true),
     *                 @OA\Property(property="data_vencimento", type="string", format="date", nullable=true),
     *                 @OA\Property(property="dias_restantes", type="integer", nullable=true),
     *                 @OA\Property(property="tipo_decreto", type="string", nullable=true, example="SE"),
     *                 @OA\Property(property="processo", type="string", example="MUNICIPAL"),
     *                 @OA\Property(property="analista", type="string", nullable=true),
     *                 @OA\Property(property="obitos", type="integer", example=0),
     *                 @OA\Property(property="feridos", type="integer", example=0),
     *                 @OA\Property(property="desalojados", type="integer", example=0),
     *                 @OA\Property(property="desabrigados", type="integer", example=0),
     *                 @OA\Property(property="desaparecidos", type="integer", example=0),
     *                 @OA\Property(property="outros_afetados", type="integer", example=0),
     *                 @OA\Property(property="danos_humanos_quantidade", type="integer", example=0),
     *                 @OA\Property(property="danos_materiais_danificadas", type="integer", example=0),
     *                 @OA\Property(property="danos_materiais_destruidas", type="integer", example=0),
     *                 @OA\Property(property="danos_materiais_valor", type="number", format="float", example=0),
     *                 @OA\Property(property="prejuizos_publicos_valor", type="number", format="float", example=0),
     *                 @OA\Property(property="prejuizos_privados_valor", type="number", format="float", example=0)
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_registros", type="integer", example=1),
     *                 @OA\Property(property="gerado_em", type="string", format="date-time"),
     *                 @OA\Property(property="filtros_aplicados", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado"),
     *     @OA\Response(response=403, description="Sem permissao rat.bi.view")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $dados = $this->exportService->getNormalizedDataForPowerBI($request);

        return response()->json([
            'sucesso' => true,
            'dados' => $dados,
            'meta' => [
                'total_registros' => count($dados),
                'gerado_em' => now()->toISOString(),
                'filtros_aplicados' => $request->except(['bi-access', 'include_deleted']),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bi/entrada/{id}",
     *     summary="Detalhe de uma entrada de processo",
     *     operationId="biEntradaShow",
     *     tags={"BI - Entrada"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=98)),
     *     @OA\Response(
     *         response=200,
     *         description="Entrada encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="sucesso", type="boolean", example=true),
     *             @OA\Property(property="dados", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Registro nao encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="sucesso", type="boolean", example=false),
     *             @OA\Property(property="mensagem", type="string", example="Registro nao encontrado")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado"),
     *     @OA\Response(response=403, description="Sem permissao rat.bi.view")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $dados = $this->queryService->showForApi($id);

        if (!$dados) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Registro nao encontrado',
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $dados,
        ]);
    }
}
