<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="SDC - Sistema de Defesa Civil API",
 *     version="1.0.0",
 *     description="API RESTful escalável para 100k+ usuários simultâneos.
 *                  Suporta webhooks, rate limiting inteligente, processamento assíncrono via Redis,
 *                  e múltiplos níveis de priorização de requisições.",
 *     @OA\Contact(
 *         email="api@sdc.gov.br",
 *         name="SDC API Support"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://sdc.gov.br/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:18001",
 *     description="Servidor de Desenvolvimento (Docker)"
 * )
 *
 * @OA\Server(
 *     url="https://api.sdc.gov.br",
 *     description="Servidor de Produção"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Autenticação via Bearer Token (Sanctum)",
 *     name="bearerAuth",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints de autenticação e autorização"
 * )
 *
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Sistema de webhooks com filas e priorização"
 * )
 *
 * @OA\Tag(
 *     name="High Performance",
 *     description="Endpoints otimizados para alta carga (100k+ usuários)"
 * )
 *
 * @OA\Tag(
 *     name="Decretacoes",
 *     description="Endpoints do modulo de Decretacoes — listagem, detalhe, export Power BI e recebimento externo"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=100),
 *         @OA\Property(property="last_page", type="integer", example=7)
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="first", type="string"),
 *         @OA\Property(property="last", type="string"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", nullable=true)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProcessoDecretacaoItem",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="processo", type="string", example="MUNICIPAL"),
 *     @OA\Property(property="reconhecimento", type="string", example="SE"),
 *     @OA\Property(property="tipo_desastre_id", type="integer", example=5),
 *     @OA\Property(property="situacao_anormalidade", type="string", example="SE"),
 *     @OA\Property(property="data_entrada", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="n_protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *     @OA\Property(property="analista", type="string", nullable=true, example="joao.silva"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ProcessoDecretacaoList",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProcessoDecretacaoItem")),
 *         @OA\Property(property="meta", type="object",
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="last_page", type="integer", example=5),
 *             @OA\Property(property="per_page", type="integer", example=15),
 *             @OA\Property(property="total", type="integer", example=75)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ReceiveProcessoRequest",
 *     type="object",
 *     required={"data_entrada", "origem", "municipio_id"},
 *     @OA\Property(property="data_entrada", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="origem", type="string", enum={"municipal", "estadual"}, example="municipal"),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="cobrade_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="situacao_anormalidade", type="string", nullable=true, enum={"N1", "SE"}, example="SE"),
 *     @OA\Property(property="n_protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *     @OA\Property(property="observacoes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="DecretacaoPowerBIExport",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="integer", example=261),
 *             @OA\Property(property="uf", type="string", example="MG"),
 *             @OA\Property(property="municipio", type="string", example="Ouro Verde de Minas"),
 *             @OA\Property(property="codigo_ibge", type="string", example="3146206"),
 *             @OA\Property(property="macroregiao", type="string", example="JEQUITINHONHAMUCURI"),
 *             @OA\Property(property="latitude", type="string", example="-18."),
 *             @OA\Property(property="longitude", type="string", example="-41."),
 *             @OA\Property(property="latitude_dec", type="number", format="float", example=-18.07),
 *             @OA\Property(property="longitude_dec", type="number", format="float", example=-41.27),
 *             @OA\Property(property="data_registro", type="string", format="date", example="2026-12-30"),
 *             @OA\Property(property="data_criacao", type="string", format="date-time"),
 *             @OA\Property(property="deletado", type="boolean", example=false),
 *             @OA\Property(property="data_delecao", type="string", format="date-time", nullable=true),
 *             @OA\Property(property="protocolo", type="string", example="MG-F-3146206-13214-20260121"),
 *             @OA\Property(property="cobrade", type="string", example="1.3.2.1.4"),
 *             @OA\Property(property="tipo_desastre", type="string", example="Tempestade local: chuvas intensas."),
 *             @OA\Property(property="status", type="string", example="Reconhecido pelo Estado e pela Uniao"),
 *             @OA\Property(property="data_fato", type="string", format="date", example="2026-01-21"),
 *             @OA\Property(property="data_decreto_municipal", type="string", format="date"),
 *             @OA\Property(property="data_publicacao_mg", type="string", format="date"),
 *             @OA\Property(property="prazo_vigencia_dias", type="integer", example=180),
 *             @OA\Property(property="data_vencimento", type="string", format="date"),
 *             @OA\Property(property="dias_restantes", type="integer", example=124),
 *             @OA\Property(property="tipo_decreto", type="string", example="SE"),
 *             @OA\Property(property="processo", type="string", example="MUNICIPAL"),
 *             @OA\Property(property="analista", type="string", example="SC Cristina"),
 *             @OA\Property(property="obitos", type="integer", example=0),
 *             @OA\Property(property="feridos", type="integer", example=0),
 *             @OA\Property(property="desalojados", type="integer", example=39),
 *             @OA\Property(property="desabrigados", type="integer", example=12),
 *             @OA\Property(property="desaparecidos", type="integer", example=0),
 *             @OA\Property(property="outros_afetados", type="integer", example=2680),
 *             @OA\Property(property="danos_humanos_quantidade", type="integer", example=2731),
 *             @OA\Property(property="danos_materiais_danificadas", type="integer", example=22),
 *             @OA\Property(property="danos_materiais_destruidas", type="integer", example=0),
 *             @OA\Property(property="danos_materiais_valor", type="number", example=122000),
 *             @OA\Property(property="prejuizos_publicos_valor", type="number", example=71400),
 *             @OA\Property(property="prejuizos_privados_valor", type="number", example=2100)
 *         )
 *     )
 * )
 */
class SwaggerController extends Controller
{
    // Este controller apenas contém as anotações do Swagger
    // A documentação é gerada automaticamente via l5-swagger
}
