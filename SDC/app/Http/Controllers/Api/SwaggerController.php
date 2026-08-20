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
 *     url="/",
 *     description="Servidor atual (mesma origem da documentacao)"
 * )
 *
 * @OA\Server(
 *     url="https://sdcdefesa.azurewebsites.net",
 *     description="Servidor de Producao (Azure App Service)"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:19444",
 *     description="Servidor de Desenvolvimento (FrankenPHP HTTP)"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Token de acesso pessoal (Bearer). Emitido por um administrador com acesso ao modulo de Permissionamento e vinculado ao usuario. Informe no campo abaixo apenas o token (o prefixo 'Bearer ' e adicionado automaticamente). Token Sanctum, nao e JWT.",
 *     name="bearerAuth",
 *     in="header",
 *     scheme="bearer",
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
 * @OA\Tag(
 *     name="Ajuda Humanitaria",
 *     description="Fornecimento de dados de Ajuda Humanitaria — saldo de estoque, liberacoes e consolidado de pedidos. Paridade com os endpoints publicos do sistema legado"
 * )
 *
 * @OA\Tag(
 *     name="RAT",
 *     description="Relatório de Atividade Técnica — listagem paginada, detalhe e recebimento externo via BI"
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
 *     title="Processo de Decretacao (formato plano)",
 *     @OA\Property(property="id", type="integer", example=261),
 *     @OA\Property(property="uf", type="string", example="MG"),
 *     @OA\Property(property="municipio", type="string", nullable=true, example="Ouro Verde de Minas"),
 *     @OA\Property(property="codigo_ibge", type="string", nullable=true, example="3146206"),
 *     @OA\Property(property="macroregiao", type="string", nullable=true, example="JEQUITINHONHAMUCURI"),
 *     @OA\Property(property="latitude", type="string", nullable=true, example="-18."),
 *     @OA\Property(property="longitude", type="string", nullable=true, example="-41."),
 *     @OA\Property(property="latitude_dec", type="number", format="float", nullable=true, example=-18.07),
 *     @OA\Property(property="longitude_dec", type="number", format="float", nullable=true, example=-41.269722),
 *     @OA\Property(property="data_registro", type="string", format="date", nullable=true, example="2026-12-30"),
 *     @OA\Property(property="data_criacao", type="string", format="date-time", nullable=true, example="2026-01-30T11:28:56.000000Z"),
 *     @OA\Property(property="deletado", type="boolean", example=false),
 *     @OA\Property(property="data_delecao", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="protocolo", type="string", nullable=true, example="MG-F-3146206-13214-20260121"),
 *     @OA\Property(property="cobrade", type="string", nullable=true, example="1.3.2.1.4"),
 *     @OA\Property(property="tipo_desastre", type="string", nullable=true, example="Tempestade local: chuvas intensas."),
 *     @OA\Property(property="status", type="string", nullable=true, example="Reconhecido pelo Estado e pela Uniao"),
 *     @OA\Property(property="data_fato", type="string", format="date", nullable=true, example="2026-01-21"),
 *     @OA\Property(property="data_decreto_municipal", type="string", format="date", nullable=true, example="2026-01-22"),
 *     @OA\Property(property="data_publicacao_mg", type="string", format="date", nullable=true, example="2026-01-22"),
 *     @OA\Property(property="prazo_vigencia_dias", type="integer", nullable=true, example=180),
 *     @OA\Property(property="data_vencimento", type="string", format="date", nullable=true, example="2026-07-21"),
 *     @OA\Property(property="dias_restantes", type="integer", nullable=true, example=85),
 *     @OA\Property(property="tipo_decreto", type="string", nullable=true, enum={"SE", "ECP"}, example="SE"),
 *     @OA\Property(property="processo", type="string", nullable=true, example="MUNICIPAL"),
 *     @OA\Property(property="analista", type="string", nullable=true, example="SC Cristina"),
 *     @OA\Property(property="obitos", type="integer", example=0),
 *     @OA\Property(property="feridos", type="integer", example=0),
 *     @OA\Property(property="desalojados", type="integer", example=39),
 *     @OA\Property(property="desabrigados", type="integer", example=12),
 *     @OA\Property(property="desaparecidos", type="integer", example=0),
 *     @OA\Property(property="outros_afetados", type="integer", example=2680),
 *     @OA\Property(property="danos_humanos_quantidade", type="integer", example=2731),
 *     @OA\Property(property="danos_materiais_danificadas", type="integer", example=22),
 *     @OA\Property(property="danos_materiais_destruidas", type="integer", example=0),
 *     @OA\Property(property="danos_materiais_valor", type="number", format="float", example=122000),
 *     @OA\Property(property="prejuizos_publicos_valor", type="number", format="float", example=71400),
 *     @OA\Property(property="prejuizos_privados_valor", type="number", format="float", example=2100)
 * )
 *
 * @OA\Schema(
 *     schema="ProcessoDecretacaoDetail",
 *     type="object",
 *     title="Detalhe de Processo de Decretacao (formato rico)",
 *     description="Resposta do GET /api/v1/decretacoes/{id}. Usa ProcessoResource (estrutura aninhada), diferente do formato plano da listagem.",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="id", type="integer", example=261),
 *         @OA\Property(property="processo", type="string", nullable=true, example="MUNICIPAL"),
 *         @OA\Property(property="origem", type="string", nullable=true, example="MUNICIPAL"),
 *         @OA\Property(property="tipo_decreto", type="string", nullable=true, enum={"SE", "ECP"}, example="SE"),
 *         @OA\Property(property="status", type="string", nullable=true, example="Reconhecido pelo Estado e pela Uniao"),
 *         @OA\Property(property="protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *         @OA\Property(property="n_protocolo_fide", type="string", nullable=true, example="2025.001.001"),
 *         @OA\Property(property="municipio_id", type="integer", nullable=true, example=123),
 *         @OA\Property(property="data_entrada", type="string", format="date", nullable=true, example="2026-01-21"),
 *         @OA\Property(property="data_entrada_formatada", type="string", nullable=true, example="21/01/2026"),
 *         @OA\Property(property="data_decreto_municipal", type="string", format="date", nullable=true),
 *         @OA\Property(property="data_publicacao_mg", type="string", format="date", nullable=true),
 *         @OA\Property(property="prazo_vigencia", type="integer", nullable=true, example=180),
 *         @OA\Property(property="data_vencimento", type="string", format="date", nullable=true, example="2026-07-21"),
 *         @OA\Property(property="dias_restantes", type="integer", nullable=true, example=85),
 *         @OA\Property(property="vigente", type="boolean", example=true),
 *         @OA\Property(property="proximo_vencer", type="boolean", example=false),
 *         @OA\Property(property="tipo_desastre_id", type="integer", nullable=true, example=5),
 *         @OA\Property(property="cobrade_id", type="integer", nullable=true, example=5),
 *         @OA\Property(property="tipo_desastre_nome", type="string", nullable=true, example="Tempestade local: chuvas intensas."),
 *         @OA\Property(property="tipo_desastre_cobrade", type="string", nullable=true, example="1.3.2.1.4"),
 *         @OA\Property(property="analista", type="string", nullable=true, example="SC Cristina"),
 *         @OA\Property(property="reconhecimento", type="string", nullable=true),
 *         @OA\Property(property="observacoes", type="string", nullable=true),
 *         @OA\Property(property="municipios_count", type="integer", example=1),
 *         @OA\Property(property="municipios", type="array",
 *             @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer", example=123),
 *                 @OA\Property(property="nome", type="string", example="Ouro Verde de Minas"),
 *                 @OA\Property(property="codigo_ibge", type="string", nullable=true, example="3146206")
 *             )
 *         ),
 *         @OA\Property(property="desastres", type="array",
 *             @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="categoria_id", type="integer", nullable=true, example=2),
 *                 @OA\Property(property="descricao", type="string", nullable=true)
 *             )
 *         ),
 *         @OA\Property(property="totais", type="object", nullable=true, description="Totais agregados (geral e por municipio): danos_humanos, danos_materiais, prejuizos_publicos, prejuizos_privados"),
 *         @OA\Property(property="pedidos_ah", type="array", @OA\Items(type="object"), description="Pedidos de ajuda humanitaria vinculados ao decreto"),
 *         @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *         @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 *     )
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
 *     title="Export Power BI Decretacoes",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array",
 *         @OA\Items(ref="#/components/schemas/ProcessoDecretacaoItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatProtocoloItem",
 *     type="object",
 *     title="RAT — Item de listagem (paginado)",
 *     description="Retornado pelo GET /api/v1/rat/protocolos (RatListResource). Campos leves para grid/tabela.",
 *     @OA\Property(property="id", type="string", format="uuid", example="018f2a3b-0000-7000-8000-000000000001"),
 *     @OA\Property(property="numero_bos", type="string", example="2025-000000001-001"),
 *     @OA\Property(property="protocolo", type="string", example="2025-000000001-001"),
 *     @OA\Property(property="sequencial_ano", type="integer", nullable=true, example=1),
 *     @OA\Property(property="status", type="string", enum={"em_andamento","finalizado"}, example="finalizado"),
 *     @OA\Property(property="status_label", type="string", example="Finalizado"),
 *     @OA\Property(property="municipio", type="string", nullable=true, example="Belo Horizonte"),
 *     @OA\Property(property="local", type="object",
 *         @OA\Property(property="municipio", type="string", nullable=true, example="Belo Horizonte"),
 *         @OA\Property(property="municipio_nome", type="string", nullable=true, example="Belo Horizonte"),
 *         @OA\Property(property="uf", type="string", nullable=true, example="MG")
 *     ),
 *     @OA\Property(property="criado_por", type="string", example="Maria Silva"),
 *     @OA\Property(property="pode_relacionar", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-23T08:53:14+00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-12-23T08:54:53+00:00")
 * )
 *
 * @OA\Schema(
 *     schema="RatProtocoloList",
 *     type="object",
 *     title="RAT — Listagem paginada",
 *     description="Resposta do GET /api/v1/rat/protocolos. Segue o padrão PaginatedResponse com success/data/meta.",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatProtocoloItem")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=120),
 *         @OA\Property(property="last_page", type="integer", example=8)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatBIExportItem",
 *     type="object",
 *     title="RAT — Item export Power BI (nested)",
 *     description="Estrutura de cada item retornado por GET /api/v1/rat/protocolos/export/power-bi. Datas em 'Y-m-d H:i:s'. Espelha o contrato do módulo PAE.",
 *     @OA\Property(property="dados_gerais", type="object",
 *         @OA\Property(property="id", type="string", format="uuid", example="018f2a3b-0000-7000-8000-000000000001"),
 *         @OA\Property(property="numero_bos", type="string", example="2025-000000001-001"),
 *         @OA\Property(property="status", type="integer", example=1, description="1=Finalizado, 0=Em andamento"),
 *         @OA\Property(property="status_descricao", type="string", example="Finalizado"),
 *         @OA\Property(property="uf", type="string", nullable=true, example="MG"),
 *         @OA\Property(property="municipio", type="string", nullable=true, example="620 - BELO HORIZONTE"),
 *         @OA\Property(property="data_fato", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="ano", type="string", nullable=true, example="2025"),
 *         @OA\Property(property="mes", type="string", nullable=true, example="12"),
 *         @OA\Property(property="cod_cobrade", type="string", nullable=true, example="Transporte aéreo"),
 *         @OA\Property(property="cobrade_descricao", type="string", nullable=true, example="Transporte aéreo"),
 *         @OA\Property(property="cod_ocorrencia", type="string", nullable=true, example="Id et enim mollitia"),
 *         @OA\Property(property="nome_operacao", type="string", nullable=true),
 *         @OA\Property(property="nome_unidade", type="string", nullable=true, example="2"),
 *         @OA\Property(property="municipio_responsavel", type="string", nullable=true, example="6230"),
 *         @OA\Property(property="codigo_unidade", type="string", nullable=true, example="123"),
 *         @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="local_pais", type="string", nullable=true, example="BR"),
 *         @OA\Property(property="local_logradouro", type="string", nullable=true, example="Rua Fortaleza"),
 *         @OA\Property(property="local_bairro", type="string", nullable=true),
 *         @OA\Property(property="local_cep", type="string", nullable=true),
 *         @OA\Property(property="local_numero", type="string", nullable=true, example="123"),
 *         @OA\Property(property="local_complemento", type="string", nullable=true),
 *         @OA\Property(property="local_tipo_ocorrencia", type="string", nullable=true, enum={"urbana","rural","rodovia","estrada","mata","montanha","rio","lago","outros"}, example="rodovia"),
 *         @OA\Property(property="data_inicio_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="data_termino_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="comunicacao_data", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="comunicacao_atendimento", type="string", nullable=true, enum={"telefone","radio","pessoal","sistema","email","outro"}, example="pessoal"),
 *         @OA\Property(property="created_at", type="string", example="2025-12-23 08:53:14"),
 *         @OA\Property(property="updated_at", type="string", example="2025-12-23 08:54:53")
 *     ),
 *     @OA\Property(property="recursos", type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="string", nullable=true),
 *             @OA\Property(property="seq", type="integer", example=0),
 *             @OA\Property(property="tipo_recurso", type="string", nullable=true, example="aquatico"),
 *             @OA\Property(property="categoria", type="string", nullable=true, example="comunicacao"),
 *             @OA\Property(property="numero_viatura", type="string", nullable=true, example="HNH 1932"),
 *             @OA\Property(property="placa", type="string", nullable=true, example="HNH 1932"),
 *             @OA\Property(property="padrao", type="string", nullable=true),
 *             @OA\Property(property="orgao", type="string", nullable=true, example="samu"),
 *             @OA\Property(property="descricao", type="string", nullable=true),
 *             @OA\Property(property="problemas", type="boolean", example=false),
 *             @OA\Property(property="descricao_problemas", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Property(property="envolvidos", type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="string", nullable=true),
 *             @OA\Property(property="tipo_pessoa", type="string", nullable=true, enum={"fisica","juridica"}, example="juridica"),
 *             @OA\Property(property="nome", type="string", nullable=true, example="João da Silva"),
 *             @OA\Property(property="cpf", type="string", nullable=true),
 *             @OA\Property(property="email", type="string", nullable=true, example="email@example.com"),
 *             @OA\Property(property="telefone_residencial", type="string", nullable=true, example="(31) 00000-0000"),
 *             @OA\Property(property="telefone_comercial", type="string", nullable=true),
 *             @OA\Property(property="sexo", type="string", nullable=true),
 *             @OA\Property(property="estado_civil", type="string", nullable=true, example="solteiro"),
 *             @OA\Property(property="data_nascimento", type="string", nullable=true, example="1990-01-15"),
 *             @OA\Property(property="cor_raca", type="string", nullable=true, example="parda"),
 *             @OA\Property(property="escolaridade", type="string", nullable=true, example="analfabeto"),
 *             @OA\Property(property="ocupacao", type="string", nullable=true),
 *             @OA\Property(property="morador_rua", type="boolean", example=false),
 *             @OA\Property(property="estrangeiro", type="boolean", example=false),
 *             @OA\Property(property="endereco_municipio", type="string", nullable=true, example="620"),
 *             @OA\Property(property="endereco_uf", type="string", nullable=true, example="MG"),
 *             @OA\Property(property="endereco_logradouro", type="string", nullable=true),
 *             @OA\Property(property="endereco_bairro", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Property(property="vistoria", type="object", nullable=true,
 *         description="null quando tem_vistoria=false",
 *         @OA\Property(property="id", type="string", nullable=true),
 *         @OA\Property(property="solicitante", type="object",
 *             @OA\Property(property="nome", type="string", nullable=true, example="MATHEUS KEVIN ESTRELA DA SILVA"),
 *             @OA\Property(property="cpf", type="string", nullable=true, example="312.312.312-38"),
 *             @OA\Property(property="telefone", type="string", nullable=true, example="(23) 13123-1231"),
 *             @OA\Property(property="endereco", type="string", nullable=true),
 *             @OA\Property(property="bairro", type="string", nullable=true),
 *             @OA\Property(property="cep", type="string", nullable=true, example="31210-260")
 *         ),
 *         @OA\Property(property="imovel", type="object",
 *             @OA\Property(property="tipo_imovel", type="string", nullable=true, example="comercial"),
 *             @OA\Property(property="tipo_construcao", type="string", nullable=true, example="outro"),
 *             @OA\Property(property="tipo_edificacao", type="string", nullable=true, example="construcoes_area_risco"),
 *             @OA\Property(property="sistema_estrutural", type="string", nullable=true, example="alvenaria"),
 *             @OA\Property(property="numero_pavimentos", type="integer", nullable=true, example=9),
 *             @OA\Property(property="estado_conservacao", type="string", nullable=true, example="pessimo"),
 *             @OA\Property(property="regime_ocupacao", type="string", nullable=true, example="proprio"),
 *             @OA\Property(property="bairro", type="string", nullable=true, example="Bonfim"),
 *             @OA\Property(property="municipio", type="integer", nullable=true, example=1580),
 *             @OA\Property(property="cep", type="string", nullable=true, example="31210-260"),
 *             @OA\Property(property="latitude", type="string", nullable=true),
 *             @OA\Property(property="longitude", type="string", nullable=true)
 *         ),
 *         @OA\Property(property="proprietario_morador", type="object",
 *             @OA\Property(property="nome", type="string", nullable=true),
 *             @OA\Property(property="telefone", type="string", nullable=true),
 *             @OA\Property(property="numero_moradores", type="integer", nullable=true, example=61),
 *             @OA\Property(property="ha_idosos", type="string", enum={"sim","nao"}, example="nao"),
 *             @OA\Property(property="ha_criancas", type="string", enum={"sim","nao"}, example="sim"),
 *             @OA\Property(property="ha_dificuldade_locomocao", type="string", enum={"sim","nao"}, example="nao")
 *         ),
 *         @OA\Property(property="infraestrutura", type="object",
 *             @OA\Property(property="abastecimento_agua", type="string", nullable=true),
 *             @OA\Property(property="esgotamento_sanitario", type="string", nullable=true),
 *             @OA\Property(property="drenagem_superficial", type="string", nullable=true),
 *             @OA\Property(property="sistema_viario_acesso", type="string", nullable=true),
 *             @OA\Property(property="tipo_revestimento", type="string", nullable=true),
 *             @OA\Property(property="numero_moradias_terreno", type="integer", nullable=true, example=79),
 *             @OA\Property(property="material_construtivo", type="string", nullable=true),
 *             @OA\Property(property="conservacao_estrutural", type="string", nullable=true)
 *         ),
 *         @OA\Property(property="analise_tecnica", type="object",
 *             @OA\Property(property="elementos_estruturais", type="string", nullable=true),
 *             @OA\Property(property="elementos_construtivos", type="string", nullable=true),
 *             @OA\Property(property="agentes_potencializadores", type="string", nullable=true),
 *             @OA\Property(property="processos_geodinamicos", type="string", nullable=true)
 *         ),
 *         @OA\Property(property="patologias", type="array", @OA\Items(type="string"),
 *             example={"Trincas","Desagregação","Instabilidade de Talude","Outros: descrição"}
 *         ),
 *         @OA\Property(property="bens_afetados", type="array", @OA\Items(type="string"),
 *             example={"Muros","Vias Públicas","Outros: descrição"}
 *         ),
 *         @OA\Property(property="orgaos_acionados", type="array", @OA\Items(type="string"),
 *             example={"CEMIG","Secretaria Municipal","Polícia Militar","CREA"}
 *         ),
 *         @OA\Property(property="encaminhamentos", type="array", @OA\Items(type="string"),
 *             example={"Interdição Parcial","Remoção Temporária","Comunicação a Órgãos"}
 *         ),
 *         @OA\Property(property="destinacao_localizacao", type="object",
 *             @OA\Property(property="tipo_destinacao", type="string", nullable=true, example="publico"),
 *             @OA\Property(property="tipo_localizacao", type="string", nullable=true, example="rural")
 *         )
 *     ),
 *     @OA\Property(property="historico", type="object",
 *         @OA\Property(property="historico_ocorrencia", type="string", enum={"sim","nao"}, example="sim"),
 *         @OA\Property(property="criado_por", type="object",
 *             @OA\Property(property="id", type="string", example="1083"),
 *             @OA\Property(property="nome", type="string", example="Matheus Kevin Estrela da Silva"),
 *             @OA\Property(property="email", type="string", example="matheus.estrela@defesacivil.mg.gov.br")
 *         ),
 *         @OA\Property(property="criado_em", type="string", example="2025-12-23 08:53:14"),
 *         @OA\Property(property="atualizado_em", type="string", example="2025-12-23 08:54:53"),
 *         @OA\Property(property="prazo_edicao", type="string", nullable=true, example="2025-12-25 08:53:14")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatBIExportResponse",
 *     type="object",
 *     title="RAT — Resposta Export Power BI",
 *     description="Resposta do GET /api/v1/rat/protocolos/export/power-bi. Sem paginação, formato nested compatível com Power BI.",
 *     @OA\Property(property="sucesso", type="boolean", example=true),
 *     @OA\Property(property="dados", type="array", @OA\Items(ref="#/components/schemas/RatBIExportItem")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="total_registros", type="integer", example=45),
 *         @OA\Property(property="gerado_em", type="string", format="date-time", example="2025-12-23T10:00:00Z"),
 *         @OA\Property(property="filtros_aplicados", type="object", description="Filtros query params ativos nesta requisição")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatProtocoloDetail",
 *     type="object",
 *     title="RAT — Detalhe completo (show / create)",
 *     description="Retornado pelo GET /api/v1/rat/protocolos/{id} e POST /api/v1/rat/protocolos (via RatResource).",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="id", type="string", format="uuid", example="018f2a3b-0000-7000-8000-000000000001"),
 *         @OA\Property(property="numero_bos", type="string", example="2025-000000001-001"),
 *         @OA\Property(property="protocolo", type="string", example="2025-000000001-001"),
 *         @OA\Property(property="status", type="string", enum={"em_andamento","finalizado"}, example="finalizado"),
 *         @OA\Property(property="status_label", type="string", example="Finalizado"),
 *         @OA\Property(property="municipio", type="string", nullable=true, example="Belo Horizonte"),
 *         @OA\Property(property="tem_vistoria", type="boolean", example=false),
 *         @OA\Property(property="dados_gerais", type="object",
 *             @OA\Property(property="data_fato", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="data_inicio_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="data_termino_atividade", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="nat_cobrade_id", type="integer", nullable=true, example=15),
 *             @OA\Property(property="nat_ocorrencia", type="string", nullable=true, example="Deslizamento de terra"),
 *             @OA\Property(property="nat_nome_operacao", type="string", nullable=true),
 *             @OA\Property(property="local_municipio_nome", type="string", nullable=true, example="Belo Horizonte"),
 *             @OA\Property(property="local_estadouf", type="string", nullable=true, example="MG"),
 *             @OA\Property(property="local_cep", type="string", nullable=true),
 *             @OA\Property(property="local_logradoura_1", type="string", nullable=true),
 *             @OA\Property(property="local_bairro", type="string", nullable=true),
 *             @OA\Property(property="local_tipo_ocorrencia", type="string", nullable=true, example="rodovia"),
 *             @OA\Property(property="comunicacao_data", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *             @OA\Property(property="comunicacao_atendimento", type="string", nullable=true, enum={"telefone","radio","pessoal","sistema","email","outro"}, example="pessoal"),
 *             @OA\Property(property="tem_vistoria", type="boolean", example=false)
 *         ),
 *         @OA\Property(property="recursos", type="array", @OA\Items(ref="#/components/schemas/RatBIExportItem/properties/recursos/items")),
 *         @OA\Property(property="envolvidos", type="array", @OA\Items(ref="#/components/schemas/RatBIExportItem/properties/envolvidos/items")),
 *         @OA\Property(property="vistoria", type="object", nullable=true),
 *         @OA\Property(property="historico", type="object",
 *             @OA\Property(property="historico_ocorrencia", type="string", nullable=true),
 *             @OA\Property(property="criado_por", type="object",
 *                 @OA\Property(property="id", type="string"),
 *                 @OA\Property(property="nome", type="string"),
 *                 @OA\Property(property="email", type="string")
 *             ),
 *             @OA\Property(property="criado_em", type="string", example="2025-12-23 08:53:14"),
 *             @OA\Property(property="atualizado_em", type="string", example="2025-12-23 08:54:53"),
 *             @OA\Property(property="prazo_edicao", type="string", nullable=true, example="2025-12-25 08:53:14")
 *         ),
 *         @OA\Property(property="anexos", type="array", @OA\Items(type="object",
 *             @OA\Property(property="id", type="string", format="uuid"),
 *             @OA\Property(property="nome_original", type="string"),
 *             @OA\Property(property="mime_type", type="string"),
 *             @OA\Property(property="tamanho_bytes", type="integer"),
 *             @OA\Property(property="url", type="string"),
 *             @OA\Property(property="created_at", type="string", format="date-time")
 *         )),
 *         @OA\Property(property="ocorrencia_origem", type="object", nullable=true,
 *             @OA\Property(property="id", type="string", format="uuid"),
 *             @OA\Property(property="numero_bos", type="string", example="2025-000000001-001")
 *         ),
 *         @OA\Property(property="ocorrencias_filhas", type="array",
 *             @OA\Items(type="object",
 *                 @OA\Property(property="id", type="string", format="uuid"),
 *                 @OA\Property(property="numero_bos", type="string", example="2025-000000001-002")
 *             )
 *         ),
 *         @OA\Property(property="criado_por", type="string", nullable=true, example="Maria Silva"),
 *         @OA\Property(property="atualizado_por", type="string", nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ReceiveRatProtocoloRequest",
 *     type="object",
 *     title="RAT — Corpo do POST /api/v1/rat/protocolos",
 *     @OA\Property(property="dados_gerais", type="object", nullable=true,
 *         @OA\Property(property="data_fato", type="string", example="2025-12-23 08:53:00"),
 *         @OA\Property(property="nat_cobrade_id", type="integer", nullable=true, example=15),
 *         @OA\Property(property="nat_nome_operacao", type="string", nullable=true),
 *         @OA\Property(property="tem_vistoria", type="boolean", example=false)
 *     ),
 *     @OA\Property(property="comunicacao", type="object", nullable=true,
 *         @OA\Property(property="tipo_solicitacao", type="string", nullable=true, enum={"telefone","radio","pessoal","sistema","email","outro"}, example="pessoal"),
 *         @OA\Property(property="data_comunicacao", type="string", nullable=true, example="2025-12-23 08:53:00"),
 *         @OA\Property(property="telefone_contato", type="string", nullable=true),
 *         @OA\Property(property="nome_solicitante", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="local", type="object", nullable=true,
 *         @OA\Property(property="uf", type="string", nullable=true, example="MG"),
 *         @OA\Property(property="municipio_id", type="integer", nullable=true, example=620)
 *     ),
 *     @OA\Property(property="endereco", type="object", nullable=true,
 *         @OA\Property(property="cep", type="string", nullable=true),
 *         @OA\Property(property="logradouro", type="string", nullable=true, example="Rua Fortaleza"),
 *         @OA\Property(property="numero", type="string", nullable=true, example="123"),
 *         @OA\Property(property="bairro", type="string", nullable=true),
 *         @OA\Property(property="tipo_localizacao", type="string", nullable=true, enum={"urbana","rural","rodovia","estrada","mata","montanha","rio","lago","outros"}, example="rodovia"),
 *         @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(property="recursos", type="array", nullable=true,
 *         @OA\Items(type="object",
 *             @OA\Property(property="tipo_recurso", type="string", nullable=true, example="aquatico"),
 *             @OA\Property(property="categoria", type="string", nullable=true, example="comunicacao"),
 *             @OA\Property(property="orgao_responsavel", type="string", nullable=true, example="samu"),
 *             @OA\Property(property="identificacao", type="string", nullable=true, example="HNH 1932"),
 *             @OA\Property(property="data_saida", type="string", nullable=true, example="2025-12-23 08:00:00"),
 *             @OA\Property(property="data_chegada", type="string", nullable=true, example="2025-12-23 10:00:00"),
 *             @OA\Property(property="km_percorrido", type="number", nullable=true)
 *         )
 *     ),
 *     @OA\Property(property="envolvidos", type="array", nullable=true,
 *         @OA\Items(type="object",
 *             @OA\Property(property="tipo_pessoa", type="string", nullable=true, enum={"fisica","juridica"}, example="fisica"),
 *             @OA\Property(property="nome", type="string", nullable=true, example="João da Silva"),
 *             @OA\Property(property="cpf", type="string", nullable=true),
 *             @OA\Property(property="sexo", type="string", nullable=true),
 *             @OA\Property(property="data_nascimento", type="string", format="date", nullable=true, example="1990-01-15"),
 *             @OA\Property(property="municipio", type="string", nullable=true),
 *             @OA\Property(property="uf", type="string", nullable=true, example="MG")
 *         )
 *     ),
 *     @OA\Property(property="vistoria", type="object", nullable=true,
 *         description="Preencher apenas se tem_vistoria=true"
 *     ),
 *     @OA\Property(property="finalize", type="boolean", nullable=true, example=false,
 *         description="true para finalizar imediatamente; false (padrão) salva como rascunho. Ao finalizar, data_fato e local.uf tornam-se obrigatórios."
 *     )
 * )
 */
class SwaggerController extends Controller
{
    // Este controller apenas contém as anotações do Swagger
    // A documentação é gerada automaticamente via l5-swagger
}
