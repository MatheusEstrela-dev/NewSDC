<?php

declare(strict_types=1);

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
    // Cada agente de modulo acrescenta a sua linha.
    'materializadores' => [
        'tdap' => TdapViagensMaterializador::class,
    ],

    // API GET /api/v1/bi/{modulo}/{tabela}.
    'api' => [
        'limite_padrao' => 1000,
        'limite_maximo' => 10000,
        'rate_por_minuto' => 60,
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
        'rat' => [],
        'decretacoes' => [],
        'ajuda' => [],
        'estoque' => [],
        'cisternas' => [],
        'pae' => [],
    ],
];
