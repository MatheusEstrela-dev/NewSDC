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
 *
 *     @OA\Contact(
 *         email="api@sdc.gov.br",
 *         name="SDC API Support"
 *     ),
 *
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
 * @OA\Server(
 *     url="https://sdcdefesa.azurewebsites.net",
 *     description="Servidor de Producao (Azure App Service)"
 * )
 * @OA\Server(
 *     url="http://localhost:19444",
 *     description="Servidor de Desenvolvimento (FrankenPHP HTTP)"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Token de acesso pessoal (Bearer). Emitido por um administrador com acesso ao modulo de Permissionamento e vinculado ao usuario. O token carrega um escopo: alcanca apenas as permissoes marcadas na emissao, sempre um subconjunto das permissoes do dono. Uma chamada fora do escopo responde 403 mesmo que o usuario tenha a permissao. Todo token tem prazo (ate 90 dias). Informe no campo abaixo apenas o token (o prefixo 'Bearer ' e adicionado automaticamente). Token Sanctum, nao e JWT.",
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
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Sistema de webhooks com filas e priorização"
 * )
 * @OA\Tag(
 *     name="High Performance",
 *     description="Endpoints otimizados para alta carga (100k+ usuários)"
 * )
 * @OA\Tag(
 *     name="Decretacoes",
 *     description="Endpoints do modulo de Decretacoes — listagem, detalhe, export Power BI e recebimento externo"
 * )
 * @OA\Tag(
 *     name="Ajuda Humanitaria",
 *     description="Fornecimento de dados de Ajuda Humanitaria — saldo de estoque, liberacoes e consolidado de pedidos. Paridade com os endpoints publicos do sistema legado"
 * )
 * @OA\Tag(
 *     name="RAT",
 *     description="Relatório de Atividade Técnica — listagem paginada, detalhe e recebimento externo via BI"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *
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
 *
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
 *
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
 *
 *             @OA\Items(type="object",
 *
 *                 @OA\Property(property="id", type="integer", example=123),
 *                 @OA\Property(property="nome", type="string", example="Ouro Verde de Minas"),
 *                 @OA\Property(property="codigo_ibge", type="string", nullable=true, example="3146206")
 *             )
 *         ),
 *         @OA\Property(property="desastres", type="array",
 *
 *             @OA\Items(type="object",
 *
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
 *
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
 *
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
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ProcessoDecretacaoItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RatProtocoloItem",
 *     type="object",
 *     title="RAT — Item de listagem (paginado)",
 *     description="Retornado pelo GET /api/v1/rat/protocolos (RatListResource). Campos leves para grid/tabela.",
 *
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
 *
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
 *
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
 *
 *         @OA\Items(type="object",
 *
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
 *
 *         @OA\Items(type="object",
 *
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
 *
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
 *
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
 *
 *             @OA\Items(type="object",
 *
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
 *
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
 *
 *         @OA\Items(type="object",
 *
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
 *
 *         @OA\Items(type="object",
 *
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
 *
 * ---------------------------------------------------------------------------
 * MODULO CISTERNA
 * ---------------------------------------------------------------------------
 * As `description` abaixo carregam o mapeamento legado -> dominio -> payload.
 * Ficam aqui, e nao num .md, porque e onde o consumidor da API de fato le.
 *
 * Origem: os 225 campos de `database/data/Cisternas.sql` (80 em sinc_cisterna,
 * 53 em _rel_fornecedor, 39 em _rel_compdec, 27 em _rel_cedec, 6 em _com, 6 em
 * _lotes, 7 em _ordem_servico, 7 em _notificacoes), conferidos contra
 * app/Modules/Cisterna/Domain/Etl/Refinadores/.
 *
 * NAO mover para app/Http/Controllers/Api/Schemas.php: aquele arquivo tem um
 * docblock solto sem classe, e o swagger-php exige que a anotacao esteja ligada
 * a um elemento PHP -- os schemas de la nunca chegam ao api-docs.json (medido:
 * ProtocoloRAT, PowerBITokenResponse e RatReceiveRequest estao ausentes).
 *
 * @OA\Schema(
 *     schema="CisternaBeneficiarioItem",
 *     type="object",
 *     title="Beneficiario do Projeto Cisterna (listagem)",
 *     description="Formato reduzido da listagem. Origem no legado: tabela `sinc_cisterna` (80 colunas).",
 *
 *     @OA\Property(property="id", type="integer", example=4201),
 *     @OA\Property(property="cpf", type="string", nullable=true, description="11 digitos, sem mascara. Legado: `sinc_cisterna.cpf` varchar(14) com mascara. 5 cadastros nao foram importados por CPF truncado na origem.", example="05924079659"),
 *     @OA\Property(property="nome", type="string", example="Maria Aparecida de Souza"),
 *     @OA\Property(property="municipio", type="string", nullable=true, description="Nome. Legado: `sinc_cisterna.codmundv` (codigo IBGE), traduzido para `municipios.id` pela ponte PonteMunicipio.", example="Janauba"),
 *     @OA\Property(property="comunidade", type="string", nullable=true, description="Legado: `sinc_cisterna.comunidade` varchar(34) de texto livre, normalizado em `cisterna_comunidades`."),
 *     @OA\Property(property="situacao_analise", type="object", description="Legado: `sinc_cisterna.aprovado` int. ATENCAO: `duplicado` (valor 5 no legado) e tombstone, nao cadastro ativo -- 516 registros. Filtre em analise.",
 *         @OA\Property(property="valor", type="string", enum={"em_edicao","aprovado","reprovado","ressalva","desconsiderado","duplicado"}, example="aprovado"),
 *         @OA\Property(property="rotulo", type="string", example="Aprovado")
 *     ),
 *     @OA\Property(property="situacao_obra", type="object", description="Legado: `sinc_cisterna.estado` (0..2). Ortogonal a situacao_analise.",
 *         @OA\Property(property="valor", type="string", enum={"processamento","envio_instalacao","instalado"}, example="instalado"),
 *         @OA\Property(property="rotulo", type="string", example="Instalado")
 *     ),
 *     @OA\Property(property="ranqueamento_ordem", type="integer", nullable=true, description="Ordem de prioridade social. Nulo na maioria: e ordenavel, nao calculado pelo sistema."),
 *     @OA\Property(property="lote", type="string", nullable=true, description="Nome do lote da ordem de servico do beneficiario."),
 *     @OA\Property(property="ordem_servico", type="string", nullable=true),
 *     @OA\Property(property="etapas_concluidas", type="array", description="Etapas de vistoria ja concluidas. Substitui os tres whereHas aninhados do legado.", @OA\Items(type="string", enum={"fornecedor","compdec","cedec"})),
 *     @OA\Property(property="numero_instalacao", type="integer", nullable=true, description="Numero do QR Code colado na cisterna. Alocado SOMENTE na etapa `fornecedor`.", example=1247)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaBeneficiarioDetail",
 *     type="object",
 *     title="Beneficiario do Projeto Cisterna (detalhe)",
 *     description="Formato completo. Origem: `sinc_cisterna` (80 colunas) mais as tabelas de relatorio. As ~54 colunas de caminho de arquivo do legado sairam do dominio: os arquivos vivem em collections do Spatie MediaLibrary.",
 *
 *     @OA\Property(property="id", type="integer", example=4201),
 *     @OA\Property(property="cpf", type="string", nullable=true, example="05924079659"),
 *     @OA\Property(property="nome", type="string", example="Maria Aparecida de Souza"),
 *     @OA\Property(property="telefone", type="string", nullable=true),
 *     @OA\Property(property="data_nascimento", type="string", format="date", nullable=true),
 *     @OA\Property(property="cadastro_unico", type="string", nullable=true, description="NIS. Legado: `sinc_cisterna.cad_unico`."),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer", example=1234),
 *         @OA\Property(property="nome", type="string", example="Janauba"),
 *         @OA\Property(property="uf", type="string", example="MG")
 *     ),
 *     @OA\Property(property="comunidade", type="object",
 *         @OA\Property(property="id", type="integer", nullable=true),
 *         @OA\Property(property="nome", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="endereco", type="string", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true, description="Legado: `sinc_cisterna.latitude` varchar(150) de texto livre, com 21 formatos distintos. 7.993 de 8.099 foram parseadas; o resto e perda irrecuperavel (truncada na origem) ou eixo trocado no cadastro. O valor original continua em `cisterna_legado_raw.doc`.", example=-15.8021456),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true, example=-43.9673012),
 *     @OA\Property(property="ordem_servico", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="nome", type="string"),
 *         @OA\Property(property="lote", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="situacao_analise", type="object",
 *         @OA\Property(property="valor", type="string", enum={"em_edicao","aprovado","reprovado","ressalva","desconsiderado","duplicado"}),
 *         @OA\Property(property="rotulo", type="string"),
 *         @OA\Property(property="observacao", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="situacao_obra", type="object",
 *         @OA\Property(property="valor", type="string", enum={"processamento","envio_instalacao","instalado"}),
 *         @OA\Property(property="rotulo", type="string")
 *     ),
 *     @OA\Property(property="ranqueamento_ordem", type="integer", nullable=true),
 *     @OA\Property(property="criterios_sociais", type="object", description="Criterios de elegibilidade do programa.",
 *         @OA\Property(property="qtd_pessoas", type="integer", nullable=true),
 *         @OA\Property(property="renda", type="number", format="float", nullable=true, description="SEM CENTAVOS: no legado `renda` e float(10,0), zero casas decimais. Perda na origem, nao na migracao -- nenhuma das 8.099 linhas tinha centavos.", example=1412),
 *         @OA\Property(property="renda_per_capita", type="number", format="float", nullable=true),
 *         @OA\Property(property="possui_deficiencia", type="boolean", nullable=true),
 *         @OA\Property(property="possui_crianca", type="boolean", nullable=true),
 *         @OA\Property(property="data_nascimento_crianca", type="string", format="date", nullable=true),
 *         @OA\Property(property="possui_idoso", type="boolean", nullable=true),
 *         @OA\Property(property="chefiada_mulher", type="boolean", nullable=true)
 *     ),
 *     @OA\Property(property="avaliacao_tecnica", type="object", description="Medidas do telhado que definem a viabilidade da captacao.",
 *         @OA\Property(property="tipo_moradia", type="string", nullable=true, enum={"propria","cedida","alugada","outros"}, description="Legado: `moradia` varchar(7) em utf8mb3 -- 'PROPRIA' com acento nao cabia e chegou corrompida em 67 cadastros. 162 linhas gravaram o literal '0' (placeholder de nao respondido) e viraram nulo."),
 *         @OA\Property(property="tipo_moradia_outro", type="string", nullable=true),
 *         @OA\Property(property="comprimento_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="largura_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="area_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="comprimento_testada", type="number", format="float", nullable=true),
 *         @OA\Property(property="num_caidas_telhado", type="integer", nullable=true),
 *         @OA\Property(property="cobertura_telhado", type="string", nullable=true, description="Legado: `coberturaTelhado`. 14 linhas com o literal '0' viraram nulo; as 434 'Ceramica' acentuadas casaram."),
 *         @OA\Property(property="cobertura_outro", type="string", nullable=true),
 *         @OA\Property(property="possui_fogao_lenha", type="boolean", nullable=true),
 *         @OA\Property(property="medida_telhado_area_fogao", type="number", format="float", nullable=true),
 *         @OA\Property(property="testada_disp_parte_fogao", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(property="atendimento_pipa", type="object", description="Legado: `atendPipa` varchar(36) que devia ser booleano. 34 cadastros gravaram ali o RESPONSAVEL ('prefeitura', 'respAtExercito', ...) em vez de sim/nao; o refino leu como atendido=sim e guardou o responsavel.",
 *         @OA\Property(property="atendido", type="boolean", nullable=true),
 *         @OA\Property(property="responsaveis", type="array", @OA\Items(type="object",
 *             @OA\Property(property="valor", type="string"),
 *             @OA\Property(property="rotulo", type="string"),
 *             @OA\Property(property="descricao", type="string", nullable=true)
 *         ))
 *     ),
 *     @OA\Property(property="responsaveis_cadastro", type="object",
 *         @OA\Property(property="agente_nome", type="string", nullable=true),
 *         @OA\Property(property="agente_cpf", type="string", nullable=true),
 *         @OA\Property(property="engenheiro_nome", type="string", nullable=true),
 *         @OA\Property(property="engenheiro_crea", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="observacoes", type="string", nullable=true),
 *     @OA\Property(property="vistorias", type="array", @OA\Items(ref="#/components/schemas/CisternaVistoriaItem")),
 *     @OA\Property(property="notificacoes", type="array", @OA\Items(ref="#/components/schemas/CisternaNotificacaoItem")),
 *     @OA\Property(property="fotos_imovel", type="array", description="ATENCAO: 72% dos cadastros do legado NAO tem o arquivo aqui. As colunas `img_*` do legado guardavam o rotulo da foto ('FRENTE', 'FUNDO'), nao o caminho; o arquivo esta no Google Drive, e a URL foi preservada em `custom_properties.origem_legado`. Extrair os ~5.800 arquivos do Drive e decisao de infraestrutura pendente.", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="thumb", type="string", nullable=true),
 *         @OA\Property(property="angulo", type="string", nullable=true),
 *         @OA\Property(property="observacao", type="string", nullable=true)
 *     )),
 *     @OA\Property(property="comprovantes", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="tipo", type="string", nullable=true),
 *         @OA\Property(property="nome", type="string")
 *     )),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="atualizado_em", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaVistoriaItem",
 *     type="object",
 *     title="Vistoria de instalacao",
 *     description="Uma linha por etapa do mesmo documento, com `unique (beneficiario_id, etapa)`. No legado eram tres tabelas: `sinc_cisterna_rel_fornecedor` (53 colunas), `sinc_cisterna_rel_compdec` (39) e `sinc_cisterna_rel_cedec` (27).",
 *
 *     @OA\Property(property="id", type="integer", example=8088),
 *     @OA\Property(property="beneficiario_id", type="integer", example=4201),
 *     @OA\Property(property="etapa", type="object",
 *         @OA\Property(property="valor", type="string", enum={"fornecedor","compdec","cedec"}, example="fornecedor"),
 *         @OA\Property(property="rotulo", type="string", example="Relatorio do Fornecedor")
 *     ),
 *     @OA\Property(property="numero_instalacao", type="integer", nullable=true, description="Numero do QR Code. Alocado SOMENTE na etapa `fornecedor` -- medido no banco: 794 de 794 no fornecedor, 0 em compdec e 0 em cedec. Nulo nas outras etapas e contrato, nao dado faltante.", example=1247),
 *     @OA\Property(property="concluida", type="boolean"),
 *     @OA\Property(property="concluida_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="engenheiro", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="crea", type="string", nullable=true),
 *         @OA\Property(property="art", type="string", nullable=true, description="Preenchido somente na etapa cedec.")
 *     ),
 *     @OA\Property(property="data_relatorio", type="string", format="date", nullable=true),
 *     @OA\Property(property="local_relatorio", type="string", nullable=true),
 *     @OA\Property(property="dados_administrativos", type="object", nullable=true, description="Chave AUSENTE fora da etapa `cedec`: so ela preenche processo, contrato e empenho.",
 *         @OA\Property(property="processo_sei", type="string", nullable=true),
 *         @OA\Property(property="contrato", type="string", nullable=true),
 *         @OA\Property(property="empenho", type="string", nullable=true),
 *         @OA\Property(property="placa_obras", type="integer", nullable=true)
 *     ),
 *     @OA\Property(property="local", type="object",
 *         @OA\Property(property="endereco", type="string", nullable=true),
 *         @OA\Property(property="bairro", type="string", nullable=true),
 *         @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(property="itens", type="array", description="Chave presente somente quando a relacao vem carregada (endpoint de detalhe).", @OA\Items(ref="#/components/schemas/CisternaItemConferido")),
 *     @OA\Property(property="observacoes", type="string", nullable=true),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaItemConferido",
 *     type="object",
 *     title="Item do checklist de instalacao",
 *     description="Uma linha por item conferido, polimorfica. No legado os 13 itens eram ~87 colunas espalhadas pelas tres tabelas de relatorio, com nomes divergentes entre elas (`calha_metros` numa, `qtd_calha` noutra, `calha_opcao` numa terceira).",
 *
 *     @OA\Property(property="item", type="string", enum={"cisterna_logo","sucao","bomba","placa","calha","tubulacao","fixacao","filtro","bloco","te_pvc","joelho_pvc","luva_pvc","cap_pvc"}, example="calha"),
 *     @OA\Property(property="rotulo", type="string", example="Calha"),
 *     @OA\Property(property="conferido", type="boolean", nullable=true),
 *     @OA\Property(property="quantidade", type="number", format="float", nullable=true),
 *     @OA\Property(property="unidade", type="string", nullable=true, example="m"),
 *     @OA\Property(property="detalhes", type="object", nullable=true, description="Subquantidades que nao cabem numa coluna. Existe para `fixacao`, que no COMPDEC se desdobra em abracadeira, bucha e parafuso."),
 *     @OA\Property(property="observacao", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaComunidadeItem",
 *     type="object",
 *     title="Comunidade atendida",
 *     description="Legado: `sinc_cisterna_com` (6 colunas). 840 comunidades em 55 municipios; pares (municipio, nome) duplicados na origem foram deduplicados, e nomes que existem em municipios distintos convivem como registros separados.",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string", example="Barreiro Grande"),
 *     @OA\Property(property="ativa", type="boolean"),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="nome", type="string"),
 *         @OA\Property(property="uf", type="string")
 *     ),
 *     @OA\Property(property="beneficiarios", type="integer", description="Contagem por `comunidade_id`, nao por nome -- o legado somava a contagem entre comunidades homonimas de municipios distintos.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaLoteItem",
 *     type="object",
 *     title="Lote de contratacao",
 *     description="Legado: `sinc_cisterna_lotes` (6 colunas), 3 linhas. Nao tem municipio: o lote e nacional e a listagem nao aplica recorte territorial.",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="data", type="string", format="date", nullable=true),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="ordens_servico", type="integer", description="Contagem de OS do lote.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaOrdemServicoItem",
 *     type="object",
 *     title="Ordem de servico",
 *     description="Legado: `sinc_cisterna_ordem_servico` (7 colunas), 7 linhas. Sem recorte territorial, mesma razao do lote.",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="lote", type="object",
 *         @OA\Property(property="id", type="integer", nullable=true),
 *         @OA\Property(property="nome", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="beneficiarios", type="integer"),
 *     @OA\Property(property="documento_url", type="string", nullable=true, description="URL do processo no SEI, vinda do legado. Nao e arquivo."),
 *     @OA\Property(property="documento_anexo", type="string", nullable=true, description="Arquivo anexado no NewSDC, que o legado nao tinha.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaNotificacaoItem",
 *     type="object",
 *     title="Notificacao de fiscalizacao",
 *     description="Legado: `sinc_cisterna_notificacoes` (7 colunas), 7 linhas -- todas dado de teste. Polimorfica: o notificavel e um beneficiario ou uma vistoria.",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="notificavel", type="object",
 *         @OA\Property(property="tipo", type="string", nullable=true, enum={"beneficiario","vistoria"}, description="Alias curto, nao o FQCN."),
 *         @OA\Property(property="id", type="integer")
 *     ),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="respondida", type="boolean"),
 *     @OA\Property(property="respondida_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="emitida_por", type="string", nullable=true, description="Nome de quem emitiu. NULO em tudo que veio do legado: os 43 usuarios de origem nao mapeiam para o NewSDC (0 casam por CPF, 0 por e-mail). O `user_id` original continua em `cisterna_legado_raw.doc`."),
 *     @OA\Property(property="documentos", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="nome", type="string")
 *     )),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true)
 * )
 */
class SwaggerController extends Controller
{
    // Este controller apenas contém as anotações do Swagger
    // A documentação é gerada automaticamente via l5-swagger
}
