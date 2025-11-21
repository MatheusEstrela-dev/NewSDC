<?php

namespace App\Http\Controllers\Api\V1\BI;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="BI - Entrada",
 *     description="Endpoints para acesso aos dados de entrada de desastres e eventos"
 * )
 */
class EntradaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/bi/entrada",
     *     summary="Lista todas as entradas de desastres e eventos",
     *     description="Retorna uma lista paginada de todas as entradas de desastres e eventos registrados no sistema",
     *     operationId="listEntradas",
     *     tags={"BI - Entrada"},
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
     *         name="uf",
     *         in="query",
     *         description="Filtrar por UF",
     *         required=false,
     *         @OA\Schema(type="string", example="MG")
     *     ),
     *     @OA\Parameter(
     *         name="municipio",
     *         in="query",
     *         description="Filtrar por município",
     *         required=false,
     *         @OA\Schema(type="string", example="Juiz de Fora")
     *     ),
     *     @OA\Parameter(
     *         name="data_inicio",
     *         in="query",
     *         description="Data de início (formato: YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="data_fim",
     *         in="query",
     *         description="Data de fim (formato: YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"Registro", "Em Análise", "Aprovado", "Rejeitado"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de entradas retornada com sucesso",
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
     *                 @OA\Property(property="data_criacao", type="string", format="date-time", example="2025-11-19T17:21:07.000000Z"),
     *                 @OA\Property(property="protocolo", type="string", example="MG-F-3136702-13321-20250613"),
     *                 @OA\Property(property="cobrade", type="string", example="1.3.3.2.1"),
     *                 @OA\Property(property="tipo_desastre", type="string", example="Onda de frio: friagem."),
     *                 @OA\Property(property="status", type="string", example="Registro"),
     *                 @OA\Property(property="data_fato", type="string", format="date", example="2025-06-13"),
     *                 @OA\Property(property="obitos", type="integer", example=0),
     *                 @OA\Property(property="feridos", type="integer", example=0),
     *                 @OA\Property(property="desalojados", type="integer", example=0),
     *                 @OA\Property(property="desabrigados", type="integer", example=0),
     *                 @OA\Property(property="desaparecidos", type="integer", example=0),
     *                 @OA\Property(property="outros_afetados", type="integer", example=50),
     *                 @OA\Property(property="danos_humanos_quantidade", type="integer", example=50),
     *                 @OA\Property(property="danos_materiais_danificadas", type="integer", example=0),
     *                 @OA\Property(property="danos_materiais_destruidas", type="integer", example=0),
     *                 @OA\Property(property="danos_materiais_valor", type="number", format="float", example=0),
     *                 @OA\Property(property="prejuizos_publicos_valor", type="number", format="float", example=0),
     *                 @OA\Property(property="prejuizos_privados_valor", type="number", format="float", example=0)
     *             )),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="pagina_atual", type="integer", example=1),
     *             @OA\Property(property="por_pagina", type="integer", example=15),
     *             @OA\Property(property="ultima_pagina", type="integer", example=7)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Conectar ao model real quando disponível
        // Por enquanto, retorna dados de exemplo baseados na estrutura real
        
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Dados de exemplo baseados na estrutura real
        $dados = [
            [
                'id' => 98,
                'uf' => 'MG',
                'municipio' => 'Juiz de Fora',
                'codigo_ibge' => '3136702',
                'macroregiao' => 'ZONA DA MATA',
                'latitude' => '-21.',
                'longitude' => '-43.',
                'latitude_dec' => -21.764167,
                'longitude_dec' => -43.350278,
                'data_registro' => '2025-11-19',
                'data_criacao' => '2025-11-19T17:21:07.000000Z',
                'deletado' => false,
                'data_delecao' => null,
                'protocolo' => 'MG-F-3136702-13321-20250613',
                'cobrade' => '1.3.3.2.1',
                'tipo_desastre' => 'Onda de frio: friagem.',
                'status' => 'Registro',
                'data_fato' => '2025-06-13',
                'data_decreto_municipal' => null,
                'data_publicacao_mg' => null,
                'prazo_vigencia_dias' => null,
                'data_vencimento' => null,
                'dias_restantes' => null,
                'tipo_decreto' => null,
                'processo' => 'MUNICIPAL',
                'analista' => null,
                'obitos' => 0,
                'feridos' => 0,
                'desalojados' => 0,
                'desabrigados' => 0,
                'desaparecidos' => 0,
                'outros_afetados' => 50,
                'danos_humanos_quantidade' => 50,
                'danos_materiais_danificadas' => 0,
                'danos_materiais_destruidas' => 0,
                'danos_materiais_valor' => 0,
                'prejuizos_publicos_valor' => 0,
                'prejuizos_privados_valor' => 0,
            ],
            [
                'id' => 85,
                'uf' => 'MG',
                'municipio' => 'Belo Horizonte',
                'codigo_ibge' => '3106200',
                'macroregiao' => 'METROPOLITANA',
                'latitude_dec' => -19.9167,
                'longitude_dec' => -43.9345,
                'data_registro' => '2025-11-18',
                'data_criacao' => '2025-11-18T14:30:00.000000Z',
                'deletado' => false,
                'data_delecao' => null,
                'protocolo' => 'MG-F-3106200-12345-20250610',
                'cobrade' => '1.2.1.1.1',
                'tipo_desastre' => 'Inundação: alagamento.',
                'status' => 'Em Análise',
                'data_fato' => '2025-06-10',
                'data_decreto_municipal' => null,
                'data_publicacao_mg' => null,
                'prazo_vigencia_dias' => null,
                'data_vencimento' => null,
                'dias_restantes' => null,
                'tipo_decreto' => null,
                'processo' => 'MUNICIPAL',
                'analista' => null,
                'obitos' => 2,
                'feridos' => 15,
                'desalojados' => 120,
                'desabrigados' => 80,
                'desaparecidos' => 1,
                'outros_afetados' => 200,
                'danos_humanos_quantidade' => 218,
                'danos_materiais_danificadas' => 45,
                'danos_materiais_destruidas' => 12,
                'danos_materiais_valor' => 250000.00,
                'prejuizos_publicos_valor' => 150000.00,
                'prejuizos_privados_valor' => 100000.00,
            ],
        ];
        
        // Aplicar filtros
        if ($request->has('uf')) {
            $dados = array_filter($dados, fn($item) => $item['uf'] === $request->get('uf'));
        }
        
        if ($request->has('municipio')) {
            $dados = array_filter($dados, fn($item) => 
                stripos($item['municipio'], $request->get('municipio')) !== false
            );
        }
        
        if ($request->has('status')) {
            $dados = array_filter($dados, fn($item) => $item['status'] === $request->get('status'));
        }
        
        // Reindexar array após filtros
        $dados = array_values($dados);
        
        $total = count($dados);
        $ultimaPagina = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $dadosPaginados = array_slice($dados, $offset, $perPage);
        
        return response()->json([
            'sucesso' => true,
            'dados' => $dadosPaginados,
            'total' => $total,
            'pagina_atual' => (int) $page,
            'por_pagina' => (int) $perPage,
            'ultima_pagina' => $ultimaPagina,
        ]);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/bi/entrada/{id}",
     *     summary="Exibe uma entrada específica",
     *     description="Retorna os detalhes completos de uma entrada de desastre/evento específica",
     *     operationId="showEntrada",
     *     tags={"BI - Entrada"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da entrada",
     *         required=true,
     *         @OA\Schema(type="integer", example=98)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Entrada encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="sucesso", type="boolean", example=true),
     *             @OA\Property(property="dados", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Entrada não encontrada"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        // TODO: Buscar do banco de dados quando model estiver disponível
        $dados = [
            'id' => $id,
            'uf' => 'MG',
            'municipio' => 'Juiz de Fora',
            'codigo_ibge' => '3136702',
            'macroregiao' => 'ZONA DA MATA',
            'latitude_dec' => -21.764167,
            'longitude_dec' => -43.350278,
            'data_registro' => '2025-11-19',
            'data_criacao' => '2025-11-19T17:21:07.000000Z',
            'protocolo' => 'MG-F-3136702-13321-20250613',
            'cobrade' => '1.3.3.2.1',
            'tipo_desastre' => 'Onda de frio: friagem.',
            'status' => 'Registro',
            'data_fato' => '2025-06-13',
            'obitos' => 0,
            'feridos' => 0,
            'desalojados' => 0,
            'desabrigados' => 0,
            'desaparecidos' => 0,
            'outros_afetados' => 50,
            'danos_humanos_quantidade' => 50,
            'danos_materiais_danificadas' => 0,
            'danos_materiais_destruidas' => 0,
            'danos_materiais_valor' => 0,
            'prejuizos_publicos_valor' => 0,
            'prejuizos_privados_valor' => 0,
        ];
        
        return response()->json([
            'sucesso' => true,
            'dados' => $dados,
        ]);
    }
}

