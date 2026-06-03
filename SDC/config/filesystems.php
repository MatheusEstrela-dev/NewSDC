<?php

// DRY: monta a config de um disco de dominio escolhendo o driver pelo ambiente.
// Producao (App Service, FS efemero) define AZURE_STORAGE_CONNECTION_STRING ->
// Azure Blob; dev sem a conexao -> disco local. Evita perda de anexos no restart.
// Retorna arrays puros (sem closures) para nao quebrar config:cache.
// $localUrl: quando informado, o disco local expoe URL publica (symlink storage:link).
// No Azure o url() do adapter resolve sozinho (SAS assinado mesmo em container privado),
// entao a config 'url' nao precisa ser propagada para o driver azure.
$azureOrLocal = static function (string $container, string $localRoot, string $visibility = 'private', ?string $localUrl = null) {
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
        'root' => storage_path($localRoot),
        'url' => $localUrl,
        'visibility' => $visibility,
        'throw' => true,
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
        'compdec' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_COMPDEC', 'sdc-compdec'), 'app/compdec'),

        // Anexos do modulo PAE (documentos por protocolo/formulario).
        'pae' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_PAE', 'sdc-pae'), 'app/pae'),

        // Anexos do modulo RAT. Servidos via URL publica: em dev usa o symlink
        // storage/app/public; em producao o url() retorna SAS assinado do blob.
        'rat' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_RAT', 'sdc-rat'), 'app/public', 'public', env('APP_URL') . '/storage'),

        // Artefatos gerados por jobs assincronos (exports CSV/XLSX/PDF).
        // Servido via App\Http\Controllers\Api\V1\TraceController::download.
        // Arquivos sao temporarios; podem ser limpos por job de retencao.
        'exports' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_EXPORTS', 'sdc-exports'), 'app/exports'),

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

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
