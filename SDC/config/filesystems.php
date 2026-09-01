<?php

// Raiz do bind mount de anexos (VM on-prem): o host monta o disco dedicado
// (ex.: /mnt/newsdc_storage) em /data/anexos no container. Quando presente,
// cada disk de modulo aponta para ANEXOS_ROOT/{MODULO} — 1 mount fisico,
// separacao por modulo em subpasta logica (PAE/, RAT/, ...), criada pelo
// proprio Flysystem no primeiro put. Modulo novo = so um disk novo aqui.
$anexosRoot = env('ANEXOS_ROOT');

// DRY: monta a config de um disco de dominio escolhendo o driver pelo ambiente.
// Precedencia: Azure Blob (AZURE_STORAGE_CONNECTION_STRING, App Service com FS
// efemero) > bind mount (ANEXOS_ROOT, VM on-prem) > storage/app local (dev puro).
// Retorna arrays puros (sem closures) para nao quebrar config:cache.
// $modulo: subpasta do modulo dentro de ANEXOS_ROOT (PAE, RAT, ...).
// $localUrl: quando informado, o disco local expoe URL publica (symlink storage:link).
// No Azure o url() do adapter resolve sozinho (SAS assinado mesmo em container privado),
// entao a config 'url' nao precisa ser propagada para o driver azure.
$azureOrLocal = static function (string $container, string $modulo, string $localRoot, string $visibility = 'private', ?string $localUrl = null) use ($anexosRoot) {
    $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');

    if (! empty($connectionString)) {
        return [
            'driver' => 'azure',
            'connection_string' => $connectionString,
            'container' => $container,
            'url' => env('AZURE_STORAGE_URL'),
            'visibility' => $visibility,
            'throw' => true,
        ];
    }

    return array_filter([
        'driver' => 'local',
        'root' => $anexosRoot ? $anexosRoot . '/' . $modulo : storage_path($localRoot),
        'url' => $localUrl,
        'visibility' => $visibility,
        'throw' => true,
        // No bind mount o app (root) e a queue (www-data em prod) escrevem na
        // mesma arvore: arquivos legiveis pelo grupo e diretorios com setgid
        // herdando o grupo www-data da raiz de cada modulo.
        'permissions' => $anexosRoot ? [
            'file' => ['public' => 0664, 'private' => 0664],
            'dir' => ['public' => 02775, 'private' => 02775],
        ] : null,
    ], static fn ($value) => $value !== null);
};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "azure"
    |
    | DRY: discos de dominio (compdec/pae/exports) usam Azure Blob quando
    | AZURE_STORAGE_CONNECTION_STRING esta presente (producao no App Service,
    | FS efemero) e local caso contrario (dev). O driver "azure" e registrado
    | em App\Providers\FilesystemServiceProvider via Storage::extend. O helper
    | $azureOrLocal (topo deste arquivo) escolhe o driver por disco.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // Disk privado do modulo COMPDEC (fotos coordenador/prefeito, anexos legais, planos de contingencia)
        // Usado pelo Spatie Media Library via collection_name (foto_coordenador, foto_prefeito, anexo_arquivo, plano_arquivo)
        'compdec' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_COMPDEC', 'sdc-compdec'), 'COMPDEC', 'app/compdec'),

        // Anexos do modulo PAE (documentos por protocolo/formulario).
        'pae' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_PAE', 'sdc-pae'), 'PAE', 'app/pae'),

        // Anexos do modulo RAT (fotos e documentos de ocorrencia). Privados,
        // como os demais modulos (requisito de arquitetura: dado sensivel de
        // Defesa Civil nao sai por symlink/URL publica): servidos por rota
        // autenticada rat.ocorrencias.attachments.show, que streama do disk.
        'rat' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_RAT', 'sdc-rat'), 'RAT', 'app/rat'),

        // Anexos do RAT LEGADO (arquivo morto). Arquivos fisicos herdados do
        // sistema antigo, organizados por id do protocolo: {root}/{id}/{arquivo}
        // (mesma convencao do legado `rat_uploads/{id}/`). Disco LOCAL puro (nao
        // usa Azure): os arquivos vivem no bind mount da VM on-prem. O root vem de
        // LEGADO_RAT_ANEXOS_ROOT; em dev cai no public/rat_uploads existente.
        // Privado: servido por rota autenticada rat.arquivados.anexo.
        'legado_rat' => [
            'driver' => 'local',
            'root' => env('LEGADO_RAT_ANEXOS_ROOT', storage_path('app/public/rat_uploads')),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Arquivos do modulo Cisterna no legado `sdc`: fotos do imovel em
        // cisterna/{cpf}/ e fotos de vistoria em
        // relatorios/cisterna/{form}/{id}/. Disco de LEITURA, usado somente
        // pelo refino do ETL para copiar para as collections do MediaLibrary.
        // Nenhum caminho novo contem CPF — o legado usava dado pessoal como
        // nome de diretorio.
        'legado_cisterna' => [
            'driver' => 'local',
            'root' => env('LEGADO_CISTERNA_ANEXOS_ROOT', storage_path('app/public/legado_cisterna')),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Artefatos gerados por jobs assincronos (exports CSV/XLSX/PDF).
        // Servido via App\Http\Controllers\Api\V1\TraceController::download.
        // Arquivos sao temporarios; podem ser limpos por job de retencao.
        'exports' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_EXPORTS', 'sdc-exports'), 'EXPORTS', 'app/exports'),

        // Anexos do modulo TDAP (comprovantes de prorrogacao de cronograma).
        'tdap' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_TDAP', 'sdc-tdap'), 'TDAP', 'app/tdap'),

        // Planos de contingencia municipais (PDF por municipio). Privados,
        // servidos por rota autenticada plancon.planos.download.
        'plancon' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_PLANCON', 'sdc-plancon'), 'PLANCON', 'app/plancon'),

        // Camada Bronze do medalhao arquivada em Parquet, particionada por
        // fonte e dia. Nao e anexo de usuario: e historico bruto imutavel,
        // lido por ferramental de dados (pandas/Power BI), nunca servido via web.
        'medalhao' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_MEDALHAO', 'sdc-medalhao'), 'MEDALHAO', 'app/medalhao'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    // Anexos de modulo sao todos privados e servidos por rota autenticada —
    // nenhum ganha symlink em public/.
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
