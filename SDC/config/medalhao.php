<?php

declare(strict_types=1);

return [
    // Dias que o payload bruto permanece no Postgres antes de virar Parquet.
    'retencao_dias' => (int) env('MEDALHAO_RETENCAO_DIAS', 30),

    // Disco Flysystem onde o rollup Parquet e gravado.
    'disco' => env('MEDALHAO_DISCO', 'medalhao'),

    // Mapa grupo -> classe que persiste os DTOs na camada Silver.
    // Contrato esperado: upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    'persistidores' => [
        'sismos' => \App\Modules\Sismos\Repositories\SismoRepository::class,
        'inmet' => \App\Modules\Inmet\Repositories\InmetRepository::class,
        'cemaden' => \App\Modules\Cemaden\Repositories\CemadenRepository::class,
    ],

    // Mapa grupo -> job que refaz as matviews da camada Gold. Fica em config
    // pelo mesmo motivo que 'persistidores': o kernel nao conhece dominio, e
    // fonte nova nao deve exigir edicao no NormalizarSilverJob.
    'refresh_gold' => [
        'sismos' => \App\Modules\Sismos\Jobs\AtualizarGoldSismosJob::class,
        'inmet' => \App\Modules\Inmet\Jobs\AtualizarGoldInmetJob::class,
        'cemaden' => \App\Modules\Cemaden\Jobs\AtualizarGoldCemadenJob::class,
    ],

    'sismos' => [
        // Janela exibida na matview do mapa.
        'janela_mapa_dias' => (int) env('MEDALHAO_SISMOS_JANELA_DIAS', 90),

        // Quadrante de Minas Gerais.
        'bbox' => [
            'min_lat' => -22.9,
            'max_lat' => -14.23,
            'min_lon' => -51.04,
            'max_lon' => -39.85,
        ],

        // Dias retroativos pedidos ao FDSN a cada coleta.
        'janela_coleta_dias' => (int) env('MEDALHAO_SISMOS_COLETA_DIAS', 7),

        'usp_fdsn_url' => env('MEDALHAO_USP_FDSN_URL', 'https://moho.iag.usp.br/fdsnws/event/1/query'),
        'unb_obsis_url' => env('MEDALHAO_UNB_OBSIS_URL', 'http://obsis.unb.br/portalsis/?pg=seism'),
    ],

    'inmet' => [
        // Token da API do INMET. Estava hardcoded em InmetApiClient; sai para
        // env porque e credencial, e porque expira sem aviso.
        'token' => env('MEDALHAO_INMET_TOKEN'),

        'inventario_url' => env('MEDALHAO_INMET_INVENTARIO_URL', 'https://apitempo.inmet.gov.br/estacoes/T'),
        'leituras_url' => env('MEDALHAO_INMET_LEITURAS_URL', 'https://apitempo.inmet.gov.br/token/estacao'),

        // Recorte por UF, nao por bbox: o inventario traz SG_ESTADO confiavel,
        // o que e mais preciso e mais barato que filtro geometrico.
        'uf' => env('MEDALHAO_INMET_UF', 'MG'),
        'somente_operantes' => (bool) env('MEDALHAO_INMET_SOMENTE_OPERANTES', true),

        // A bbox aqui NAO filtra nada: serve so para enquadrar o mapa na
        // entrega. Nos sismos os dois usos coincidiam; aqui nao.
        'bbox' => [
            'min_lat' => -22.9,
            'max_lat' => -14.23,
            'min_lon' => -51.04,
            'max_lon' => -39.85,
        ],

        // Requisicoes simultaneas no Http::pool. Medido em 2026-09-01: 12
        // concorrentes em menos de 1s, entao as 68 estacoes de MG cabem folgado
        // nos 300s de timeout do worker da fila medalhao.
        'concorrencia' => (int) env('MEDALHAO_INMET_CONCORRENCIA', 20),
    ],

    'cemaden' => [
        // Feed publico da rede automatica do CEMADEN, sem token e sem login.
        // Devolve o Brasil inteiro (5789 estacoes) numa requisicao; o recorte
        // por UF acontece no ingestor.
        //
        // A janela e fixa em 24h porque e a unica publicada: 311_1, 311_3,
        // 311_6, 311_12 e 311_72 responderam 404 em 2026-09-02. O numero 311 e
        // o codigo do produto de estacoes automaticas.
        'feed_url' => env('MEDALHAO_CEMADEN_FEED_URL', 'https://resources.cemaden.gov.br/dados/311_24.json'),

        'uf' => env('MEDALHAO_CEMADEN_UF', 'MG'),

        // 2,1 MB por resposta: o timeout default de 30s do Http fica curto em
        // rede corporativa com proxy.
        'timeout' => (int) env('MEDALHAO_CEMADEN_TIMEOUT', 60),

        // A bbox aqui NAO filtra nada: serve so para enquadrar o mapa na
        // entrega, mesma semantica da bbox do inmet.
        'bbox' => [
            'min_lat' => -22.9,
            'max_lat' => -14.23,
            'min_lon' => -51.04,
            'max_lon' => -39.85,
        ],
    ],
];
