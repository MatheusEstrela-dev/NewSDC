<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    | Disk usado pelo Spatie Media Library para armazenar fotos, anexos legais
    | e planos de contingencia do modulo. Configurado em config/filesystems.php.
    */
    'disk' => env('COMPDEC_DISK', 'compdec'),

    /*
    |--------------------------------------------------------------------------
    | Threshold de Queries por Request
    |--------------------------------------------------------------------------
    | Quando uma request do modulo COMPDEC excede esse numero de queries,
    | o QueryThresholdMiddleware loga um warning no canal "compdec-perf"
    | com top 5 queries lentas + duplicatas detectadas.
    */
    'query_threshold' => (int) env('COMPDEC_QUERY_THRESHOLD', 15),

    /*
    |--------------------------------------------------------------------------
    | Validade Padrao de Anexos
    |--------------------------------------------------------------------------
    | Numero de dias usado como validade padrao para um anexo legal cadastrado
    | sem data_validade explicita (lei, decreto, portaria, etc).
    | Default: 365 dias (1 ano), igual ao comportamento legado.
    */
    'anexo_validade_padrao_dias' => (int) env('COMPDEC_ANEXO_VALIDADE_DIAS', 365),

    /*
    |--------------------------------------------------------------------------
    | Janela de Alerta de Vencimento
    |--------------------------------------------------------------------------
    | Numero de dias antes do vencimento em que o anexo entra em status
    | "prox_vencimento" (alerta) e o command de notificacao envia warning.
    */
    'anexo_alerta_vencimento_dias' => (int) env('COMPDEC_ANEXO_ALERTA_DIAS', 30),

    /*
    |--------------------------------------------------------------------------
    | Validade do Plano de Contingencia (em anos)
    |--------------------------------------------------------------------------
    | Um plano ativo enviado ha mais tempo que isso conta como irregular no
    | painel estadual de cobertura (/plancon). O legado nao tinha esse
    | conceito: so um flag `com_comdec.plano_cont` ja defasado.
    */
    'plano_validade_anos' => (int) env('COMPDEC_PLANO_VALIDADE_ANOS', 5),

    /*
    |--------------------------------------------------------------------------
    | Limites de Upload (em bytes)
    |--------------------------------------------------------------------------
    | Limites por tipo de arquivo. Mantem paridade com o sistema legado.
    */
    'upload_limits' => [
        'foto_coordenador' => 300 * 1024,         // 300 KB (legado)
        'foto_prefeito'    => 300 * 1024,         // 300 KB
        'anexo_arquivo'    => 2 * 1024 * 1024,    // 2 MB (legado: compdecleis)
        'plano_arquivo'    => 20 * 1024 * 1024,   // 20 MB (legado: plano)
    ],

    /*
    |--------------------------------------------------------------------------
    | Mime Types Aceitos
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => [
        'foto' => ['image/jpeg', 'image/png', 'image/webp'],
        'documento' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caminhos do Sistema Legado (ETL)
    |--------------------------------------------------------------------------
    | Localizacoes no storage do legado (sdc) para o ETL copiar arquivos.
    | Devem ser ajustados via .env caso a montagem do volume mude.
    */
    'legacy_paths' => [
        'foto_compdec' => env('COMPDEC_LEGACY_FOTO_COMPDEC', '/legacy/storage/app/public/compdec_fotos'),
        'foto_prefeito' => env('COMPDEC_LEGACY_FOTO_PREFEITO', '/legacy/storage/app/public/prefeitura_fotos'),
        'anexos' => env('COMPDEC_LEGACY_ANEXOS', '/legacy/storage/app/public/compdecleis'),

        /*
        | Plano de contingencia teve DOIS sistemas gravando arquivo, e por isso
        | e uma lista de candidatos, tentada em ordem:
        |
        |  1. gestaocedec (PHP puro) -- 618 dos 619 registros, de 2019 a 2022.
        |     Classe.Anexo::uploadSimple() move para DOCUMENT_ROOT/anexo/planoCont.
        |     Nomes no padrao `Plano_<data>_V.<n>.<ext>`.
        |  2. sdc (Laravel) -- 1 registro, de 2022-08. CompdecUploadPlanoController
        |     usa storeAs('public/plano'). Nomes `<compdec_id>-<timestamp>.<ext>`.
        |     Esta pasta pode nem existir no servidor, justamente por ter 1 arquivo.
        */
        'planos' => array_values(array_filter([
            env('COMPDEC_LEGACY_PLANOS_GESTAOCEDEC', '/legacy/gestaocedec/anexo/planoCont'),
            env('COMPDEC_LEGACY_PLANOS', '/legacy/storage/app/public/plano'),
        ])),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conexao do Banco Legado (ETL)
    |--------------------------------------------------------------------------
    | Nome da connection (em config/database.php) que aponta para o banco
    | sdc legado. Configurada como read-only para o ETL.
    | Default 'legacy' = nome ja existente em database.php (compatibilidade).
    */
    'legacy_connection' => env('COMPDEC_LEGACY_CONNECTION', 'legacy'),
];
