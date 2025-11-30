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
 *     url="http://localhost:8000",
 *     description="Servidor de Desenvolvimento"
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
 */
class SwaggerController extends Controller
{
    // Este controller apenas contém as anotações do Swagger
    // A documentação é gerada automaticamente via l5-swagger
}
