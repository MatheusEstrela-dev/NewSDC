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
 *
 * @OA\Schema(
 *     schema="ProcessoDecretacaoItem",
 *     type="object",
 *     title="Processo de Decretacao",
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
 *     title="Lista Paginada de Processos",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ProcessoDecretacaoItem")
 *         ),
 *         @OA\Property(
 *             property="meta",
 *             type="object",
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
 *     title="Request de Recepcao de Processo Externo",
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
 *     title="Export Power BI Decretacoes",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="municipio_id", type="integer"),
 *             @OA\Property(property="municipio_nome", type="string"),
 *             @OA\Property(property="tipo_decreto", type="string"),
 *             @OA\Property(property="data_entrada", type="string", format="date"),
 *             @OA\Property(property="obitos", type="integer"),
 *             @OA\Property(property="feridos", type="integer"),
 *             @OA\Property(property="desabrigados", type="integer"),
 *             @OA\Property(property="desalojados", type="integer")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatReceiveRequest",
 *     type="object",
 *     title="Request de Recepcao de Protocolo RAT",
 *     description="Campos conforme ReceiveRatBIRequest. Nenhum campo de primeiro nivel e obrigatorio na validacao atual.",
 *     @OA\Property(
 *         property="dados_gerais",
 *         type="object",
 *         @OA\Property(property="data_fato", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="data_inicio_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="data_termino_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="nat_cobrade_id", type="string", nullable=true),
 *         @OA\Property(property="nat_nome_operacao", type="string", nullable=true),
 *         @OA\Property(property="tem_vistoria", type="boolean", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="comunicacao",
 *         type="object",
 *         @OA\Property(property="tipo_solicitacao", type="string", nullable=true, enum={"telefone", "radio", "pessoal", "sistema", "email", "outro"}),
 *         @OA\Property(property="data_comunicacao", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="telefone_contato", type="string", nullable=true),
 *         @OA\Property(property="nome_solicitante", type="string", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="local",
 *         type="object",
 *         @OA\Property(property="pais_id", type="integer", nullable=true),
 *         @OA\Property(property="uf", type="string", nullable=true, example="MG"),
 *         @OA\Property(property="municipio_id", type="string", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="endereco",
 *         type="object",
 *         @OA\Property(property="cep", type="string", nullable=true),
 *         @OA\Property(property="logradouro", type="string", nullable=true),
 *         @OA\Property(property="numero", type="string", nullable=true),
 *         @OA\Property(property="complemento", type="string", nullable=true),
 *         @OA\Property(property="bairro", type="string", nullable=true),
 *         @OA\Property(property="km", type="string", nullable=true),
 *         @OA\Property(property="cruzamento", type="string", nullable=true),
 *         @OA\Property(property="ponto_referencia", type="string", nullable=true),
 *         @OA\Property(property="tipo_localizacao", type="string", nullable=true, enum={"urbana", "rural", "rodovia", "estrada", "mata", "montanha", "rio", "lago", "outros"}),
 *         @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="recursos",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="tipo_recurso", type="string", nullable=true),
 *             @OA\Property(property="categoria", type="string", nullable=true),
 *             @OA\Property(property="orgao_responsavel", type="string", nullable=true),
 *             @OA\Property(property="identificacao", type="string", nullable=true),
 *             @OA\Property(property="condutor", type="string", nullable=true),
 *             @OA\Property(property="descricao", type="string", nullable=true),
 *             @OA\Property(property="data_saida", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="data_chegada", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="km_percorrido", type="number", format="float", nullable=true),
 *             @OA\Property(property="local_origem", type="string", nullable=true),
 *             @OA\Property(property="local_destino", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Property(
 *         property="envolvidos",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="tipo_pessoa", type="string", nullable=true),
 *             @OA\Property(property="cpf", type="string", nullable=true),
 *             @OA\Property(property="nome", type="string", nullable=true),
 *             @OA\Property(property="nome_social", type="string", nullable=true),
 *             @OA\Property(property="data_nascimento", type="string", format="date", nullable=true),
 *             @OA\Property(property="idade_aparente", type="integer", nullable=true),
 *             @OA\Property(property="sexo", type="string", nullable=true),
 *             @OA\Property(property="nome_mae", type="string", nullable=true),
 *             @OA\Property(property="nome_pai", type="string", nullable=true),
 *             @OA\Property(property="ocupacao", type="string", nullable=true),
 *             @OA\Property(property="escolaridade", type="string", nullable=true),
 *             @OA\Property(property="cep", type="string", nullable=true),
 *             @OA\Property(property="uf", type="string", nullable=true),
 *             @OA\Property(property="municipio", type="string", nullable=true),
 *             @OA\Property(property="logradouro", type="string", nullable=true),
 *             @OA\Property(property="bairro", type="string", nullable=true),
 *             @OA\Property(property="numero", type="string", nullable=true),
 *             @OA\Property(property="complemento", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Property(
 *         property="vistoria",
 *         type="object",
 *         @OA\Property(property="solicitante", type="object"),
 *         @OA\Property(property="imovel", type="object"),
 *         @OA\Property(property="estrutura", type="object"),
 *         @OA\Property(property="moradores", type="object")
 *     ),
 *     @OA\Property(property="finalize", type="boolean", nullable=true, example=false)
 * )
 */

