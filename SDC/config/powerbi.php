<?php

declare(strict_types=1);

use App\Modules\PowerBi\Materializadores\AjudaMaterializador;
use App\Modules\PowerBi\Materializadores\CisternasBeneficiariosMaterializador;
use App\Modules\PowerBi\Materializadores\DecretacoesProcessosMaterializador;
use App\Modules\PowerBi\Materializadores\EstoqueMaterializador;
use App\Modules\PowerBi\Materializadores\PaeEmpreendimentosMaterializador;
use App\Modules\PowerBi\Materializadores\RatOcorrenciasMaterializador;
use App\Modules\PowerBi\Materializadores\TdapViagensMaterializador;

/*
|--------------------------------------------------------------------------
| Saida de BI (sdc_powerbi)
|--------------------------------------------------------------------------
|
| Tudo aqui so tem efeito com DB_POWERBI_CONNECTION definida
| (ver App\Support\Database\ConexaoPowerBi).
*/
return [
    // Janela de seguranca (segundos) aplicada aos dois lados da leitura da
    // fonte: o limite superior recua da replicacao e o inferior recua do cursor,
    // para nao perder commits tardios. O reprocessamento e inofensivo (hash).
    'margem_segundos' => (int) env('POWERBI_MARGEM_SEGUNDOS', 300),

    // Linhas por lote de leitura/gravacao.
    'lote' => (int) env('POWERBI_LOTE', 1000),

    // Sal do HMAC sha256 de CPF e cadastro unico em bi_cisternas (contagem de
    // distintos sem expor o documento). Obrigatorio; trocar o sal troca todos os hashes.
    'hash_sal' => env('POWERBI_HASH_SAL'),

    // Trava: so le da fonte se ela for standby (replica). Em homolog/prod fica
    // true para a materializacao nunca competir com o lider do site.
    'fonte_exige_standby' => (bool) env('POWERBI_FONTE_EXIGE_STANDBY', false),

    // Timeout (s) do job de materializacao; retry_after da fila e maior.
    'timeout_job' => 900,

    // Flag por modulo do endpoint antigo: ao_vivo (service de export, como hoje)
    // ou materializado (le do sdc_powerbi). Uso: SaidaBi::materializada('rat').
    'saida' => [
        'tdap' => env('POWERBI_SAIDA_TDAP', 'ao_vivo'),
        'rat' => env('POWERBI_SAIDA_RAT', 'ao_vivo'),
        'decretacoes' => env('POWERBI_SAIDA_DECRETACOES', 'ao_vivo'),
        'ajuda' => env('POWERBI_SAIDA_AJUDA', 'ao_vivo'),
        'estoque' => env('POWERBI_SAIDA_ESTOQUE', 'ao_vivo'),
        'cisternas' => env('POWERBI_SAIDA_CISTERNAS', 'ao_vivo'),
        'pae' => env('POWERBI_SAIDA_PAE', 'ao_vivo'),
    ],

    // modulo => classe que implementa Contracts\Materializador (ver MaterializadorBase).
    'materializadores' => [
        'tdap' => TdapViagensMaterializador::class,
        'rat' => RatOcorrenciasMaterializador::class,
        'decretacoes' => DecretacoesProcessosMaterializador::class,
        'ajuda' => AjudaMaterializador::class,
        'estoque' => EstoqueMaterializador::class,
        'cisternas' => CisternasBeneficiariosMaterializador::class,
        'pae' => PaeEmpreendimentosMaterializador::class,
    ],

    // API GET /api/v1/bi/{modulo}/{tabela}.
    'api' => [
        'limite_padrao' => 1000,
        'limite_maximo' => 10000,
        'rate_por_minuto' => 60,
    ],

    // Arquivos: Parquet noturno (powerbi:publicar-arquivos, dt=YYYY-MM-DD) e
    // arquivo sob demanda (POST /api/v1/bi/{modulo}/{tabela}/arquivos).
    'arquivos' => [
        'disco' => 'powerbi',
        'retencao_dias' => (int) env('POWERBI_ARQUIVOS_RETENCAO_DIAS', 7),
        'horario' => '03:15',
        'conexao_fila' => env('POWERBI_ARQUIVOS_CONEXAO_FILA', 'redis-powerbi'),
        'fila' => 'powerbi',
        'prefixo_sob_demanda' => 'sob-demanda',
        'url_temporaria_minutos' => 15,
        'limite_listagem' => 100,
    ],

    /*
    | Catalogo (whitelist) por modulo e tabela. So o que esta aqui e
    | materializado e exposto pela API. Chaves de cada tabela:
    |   schema      schema bi_* do sdc_powerbi
    |   pk          colunas da chave primaria
    |   colunas     colunas de dados (inclui a PK; sem colunas de controle)
    |   estrategia  incremental (keyset updated_at,id) | completa (staging + merge)
    |   filtros     colunas aceitas como ?coluna=valor na API (igualdade)
    |   expostas    opcional: subconjunto de colunas devolvido pela API (default: colunas)
    |   pai         opcional: ['coluna' => fk] para tabela filha (apaga e reinsere por pai)
    | LGPD: nenhuma coluna de CPF, nome de pessoa, telefone, e-mail ou endereco.
    */
    'catalogo' => [
        'tdap' => [
            'viagens' => [
                'schema' => 'bi_tdap',
                'pk' => ['viagem_id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'viagem_id', 'data_registro', 'data_aprovacao', 'cronograma_id', 'cronograma_numero',
                    'empenho', 'nota_empenho', 'cronograma_inicio', 'cronograma_final', 'ata_id', 'ata_numero',
                    'lote_id', 'lote_numero', 'municipio_id', 'municipio_nome', 'municipio_uf', 'prestador_id',
                    'prestador_nome', 'prestador_cnpj', 'caminhao_id', 'caminhao_placa', 'capacidade_m3',
                    'valor_m3', 'valor_viagem',
                ],
                'filtros' => ['municipio_id', 'prestador_id', 'cronograma_id', 'lote_id', 'ata_id'],
            ],
        ],
        'rat' => [
            'ocorrencias' => [
                'schema' => 'bi_rat',
                'pk' => ['id'],
                'estrategia' => 'incremental',
                'colunas' => [
                    'id', 'numero_bos', 'status', 'status_descricao', 'uf', 'municipio', 'data_fato', 'ano', 'mes',
                    'cod_cobrade', 'cobrade_descricao', 'cod_ocorrencia', 'nome_operacao', 'nome_unidade',
                    'municipio_responsavel', 'codigo_unidade', 'latitude', 'longitude', 'local_pais', 'local_bairro',
                    'local_tipo_ocorrencia', 'data_inicio_atividade', 'data_termino_atividade', 'comunicacao_data',
                    'comunicacao_atendimento', 'criado_em', 'atualizado_em', 'prazo_edicao', 'qtd_recursos',
                    'qtd_envolvidos', 'documento',
                ],
                'filtros' => ['status', 'ano', 'mes', 'uf', 'cod_cobrade'],
            ],
            'recursos' => [
                'schema' => 'bi_rat',
                'pk' => ['ocorrencia_id', 'seq'],
                'estrategia' => 'incremental',
                'pai' => ['coluna' => 'ocorrencia_id'],
                'colunas' => ['ocorrencia_id', 'seq', 'tipo_recurso', 'categoria', 'numero_viatura', 'placa', 'orgao', 'descricao'],
                'filtros' => ['ocorrencia_id', 'tipo_recurso'],
            ],
            'vistorias' => [
                'schema' => 'bi_rat',
                'pk' => ['ocorrencia_id'],
                'estrategia' => 'incremental',
                'pai' => ['coluna' => 'ocorrencia_id'],
                'colunas' => [
                    'ocorrencia_id', 'tipo_area', 'tipo_imovel', 'tipo_construcao', 'tipo_edificacao',
                    'tipo_terreno_relevo', 'sistema_estrutural', 'numero_pavimentos', 'estado_conservacao',
                    'regime_ocupacao', 'bairro', 'municipio', 'numero_moradores', 'ha_idosos', 'ha_criancas',
                    'ha_dificuldade_locomocao', 'abastecimento_agua', 'esgotamento_sanitario', 'drenagem_superficial',
                    'sistema_viario_acesso', 'tipo_revestimento', 'condicoes_acesso', 'numero_moradias_terreno',
                    'distancia_encosta', 'material_construtivo', 'conservacao_estrutural', 'elementos_estruturais',
                    'elementos_construtivos', 'agentes_potencializadores', 'processos_geodinamicos', 'patologias',
                    'bens_afetados', 'orgaos_acionados', 'encaminhamentos', 'tipo_destinacao', 'tipo_localizacao',
                ],
                'filtros' => ['ocorrencia_id'],
            ],
            'envolvidos_agregado' => [
                'schema' => 'bi_rat',
                'pk' => ['ocorrencia_id', 'tipo_pessoa', 'sexo', 'condicao_fisica', 'lesao_grau'],
                'estrategia' => 'incremental',
                'pai' => ['coluna' => 'ocorrencia_id'],
                'colunas' => [
                    'ocorrencia_id', 'tipo_pessoa', 'sexo', 'condicao_fisica', 'lesao_grau', 'quantidade',
                    'qtd_morador_rua', 'qtd_estrangeiro', 'qtd_turista',
                ],
                'filtros' => ['ocorrencia_id', 'sexo', 'lesao_grau'],
            ],
        ],
        'decretacoes' => [
            'processos' => [
                'schema' => 'bi_decretacoes',
                'pk' => ['id', 'municipio_id'],
                'estrategia' => 'incremental',
                'colunas' => [
                    'id', 'municipio_id', 'uf', 'municipio', 'codigo_ibge', 'macroregiao', 'latitude_dec',
                    'longitude_dec', 'data_registro', 'data_criacao', 'deletado', 'data_delecao', 'redec_id',
                    'redec', 'protocolo', 'cobrade', 'tipo_desastre', 'status', 'data_fato',
                    'data_decreto_municipal', 'data_publicacao_mg', 'prazo_vigencia_dias', 'data_vencimento',
                    'tipo_decreto', 'processo', 'analista', 'obitos', 'feridos', 'desalojados', 'desabrigados',
                    'desaparecidos', 'outros_afetados', 'danos_humanos_quantidade', 'danos_materiais_danificadas',
                    'danos_materiais_destruidas', 'danos_materiais_valor', 'prejuizos_publicos_valor',
                    'prejuizos_privados_valor', 'danos_ambientais_marcados',
                    'danos_ambientais_agua', 'danos_ambientais_agua_intensidade',
                    'danos_ambientais_ar', 'danos_ambientais_ar_intensidade',
                    'danos_ambientais_solo', 'danos_ambientais_solo_intensidade',
                    'danos_ambientais_hidrico', 'danos_ambientais_hidrico_intensidade',
                    'danos_ambientais_incendio', 'danos_ambientais_incendio_intensidade',
                ],
                'filtros' => ['municipio_id', 'codigo_ibge', 'redec_id', 'status', 'processo', 'tipo_decreto', 'deletado'],
            ],
        ],
        'ajuda' => [
            // Completas: sem updated_at confiavel (ver AjudaMaterializador).
            'liberacoes' => [
                'schema' => 'bi_ajuda',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'codigo_legado', 'data_liberacao', 'hora_liberacao', 'evento', 'status', 'situacao',
                    'municipio_id', 'codmundv', 'municipio', 'deposito_id', 'deposito', 'items_quant',
                ],
                'filtros' => ['municipio_id', 'deposito_id', 'evento', 'status'],
            ],
            'liberacao_itens' => [
                'schema' => 'bi_ajuda',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'liberacao_id', 'municipio_id', 'codmundv', 'municipio', 'data_libera', 'quantidade',
                    'id_material', 'material', 'evento', 'deposito', 'status',
                ],
                'filtros' => ['liberacao_id', 'municipio_id', 'evento', 'status'],
            ],
            'pedidos_itens' => [
                'schema' => 'bi_ajuda',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'pedido_id', 'status', 'status_nome', 'descricao_item', 'tp_item', 'municipio_id',
                    'municipio', 'num_decreto', 'qtd',
                ],
                'filtros' => ['pedido_id', 'municipio_id', 'status', 'num_decreto'],
            ],
            'saldos_cesta' => [
                'schema' => 'bi_ajuda',
                'pk' => ['deposito_id', 'material_ah_id'],
                'estrategia' => 'completa',
                'colunas' => ['deposito_id', 'material_ah_id', 'deposito', 'material', 'saldo', 'valor', 'peso'],
                'filtros' => ['deposito_id', 'material_ah_id'],
            ],
        ],
        'estoque' => [
            // produtos e lotes: completas (saldo derivado, status depende da data).
            // movimentacoes: so insercao, cursor por id.
            'produtos' => [
                'schema' => 'bi_estoque',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'sku', 'nome', 'categoria', 'unidade_base', 'patrimonio', 'estoque_minimo',
                    'controla_validade', 'ativo', 'saldo_total', 'saldo_disponivel',
                ],
                'filtros' => ['categoria', 'ativo'],
            ],
            'lotes' => [
                'schema' => 'bi_estoque',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'produto_id', 'lote', 'validade', 'fornecedor', 'endereco', 'saldo',
                    'almoxarifado_id', 'almoxarifado', 'status', 'bloqueado',
                ],
                'filtros' => ['produto_id', 'almoxarifado_id', 'status'],
            ],
            'movimentacoes' => [
                'schema' => 'bi_estoque',
                'pk' => ['id'],
                'estrategia' => 'incremental',
                'colunas' => [
                    'id', 'data', 'tipo', 'produto_id', 'quantidade', 'lote_id', 'almoxarifado_id',
                    'saldo_apos', 'romaneio', 'destino_tipo', 'destino_municipio_id',
                ],
                'filtros' => ['tipo', 'produto_id', 'almoxarifado_id', 'romaneio', 'destino_municipio_id'],
            ],
        ],
        'cisternas' => [
            'beneficiarios' => [
                'schema' => 'bi_cisternas',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'municipio_id', 'municipio', 'comunidade', 'situacao_analise', 'cpf_hash',
                    'cadastro_unico_hash', 'qtd_pessoas', 'renda', 'renda_per_capita', 'tipo_moradia',
                    'comprimento_telhado', 'largura_telhado', 'area_telhado', 'comprimento_testada',
                    'num_caidas_telhado', 'cobertura_telhado', 'possui_fogao_lenha', 'medida_telhado_area_fogao',
                    'testada_disp_parte_fogao', 'atendido_por_pipa', 'pipa_defesa_civil', 'pipa_exercito',
                    'pipa_particular', 'pipa_prefeitura', 'pipa_outros',
                ],
                // Hashes (bytea) ficam fora do JSON da API; servem a contagem de distintos via SQL/arquivo.
                'expostas' => [
                    'id', 'municipio_id', 'municipio', 'comunidade', 'situacao_analise', 'qtd_pessoas', 'renda',
                    'renda_per_capita', 'tipo_moradia', 'comprimento_telhado', 'largura_telhado', 'area_telhado',
                    'comprimento_testada', 'num_caidas_telhado', 'cobertura_telhado', 'possui_fogao_lenha',
                    'medida_telhado_area_fogao', 'testada_disp_parte_fogao', 'atendido_por_pipa', 'pipa_defesa_civil',
                    'pipa_exercito', 'pipa_particular', 'pipa_prefeitura', 'pipa_outros',
                ],
                'filtros' => ['municipio_id', 'situacao_analise'],
            ],
        ],
        'pae' => [
            // Completa: municipio, empreendedor e protocolo mudam sem tocar updated_at.
            'empreendimentos' => [
                'schema' => 'bi_pae',
                'pk' => ['id'],
                'estrategia' => 'completa',
                'colunas' => [
                    'id', 'nome', 'status', 'municipio_id', 'municipio', 'empreendedor_id', 'empreendedor',
                    'empreendedor_cnpj', 'regiao_id', 'm_construcao', 'material', 'finalidade', 'volume',
                    'pop_zas', 'orgao_fisc', 'mina', 'protocolo_status', 'protocolo_entrada', 'criado_em',
                    'atualizado_em',
                ],
                'filtros' => ['municipio_id', 'status', 'empreendedor_id', 'regiao_id'],
            ],
        ],
    ],
];
