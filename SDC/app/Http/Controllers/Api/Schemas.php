<?php

/**
 * Schemas do Swagger para a API SDC
 * 
 * Este arquivo contém todas as definições de schemas utilizadas na documentação Swagger.
 * Os schemas devem ser definidos aqui para garantir que sejam processados antes das referências.
 */

/**
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
 *     schema="ProtocoloRAT",
 *     type="object",
 *     title="Protocolo RAT",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numero", type="string", example="2025/001"),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="tipo", type="string", example="Vistoria Técnica"),
 *     @OA\Property(property="status", type="string", example="em_analise"),
 *     @OA\Property(property="data", type="string", format="date", example="2025-01-20")
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
 * 
 * @OA\Schema(
 *     schema="PowerBITokenResponse",
 *     type="object",
 *     title="Resposta de Token Power BI",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="token", type="string", example="a1b2c3d4e5f6..."),
 *         @OA\Property(property="expires_in", type="integer", example=3600),
 *         @OA\Property(property="apis", type="array", @OA\Items(type="string"), example={"pae", "rat", "tdap", "bi"}),
 *         @OA\Property(property="endpoints", type="object")
 *     )
 * )
 */

