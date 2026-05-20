<?php

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
    | Supported Drivers: "local", "ftp", "sftp", "s3"
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
        'compdec' => [
            'driver' => 'local',
            'root' => storage_path('app/compdec'),
            'visibility' => 'private',
            'throw' => true,
        ],

        'pae' => [
            'driver' => 'local',
            'root' => storage_path('app/pae'),
            'visibility' => 'private',
            'throw' => true,
        ],

        // Artefatos gerados por jobs assincronos (exports CSV/XLSX/PDF).
        // Servido via App\Http\Controllers\Api\V1\TraceController::download.
        // Arquivos sao temporarios; podem ser limpos por job de retencao.
        'exports' => [
            'driver' => 'local',
            'root' => storage_path('app/exports'),
            'visibility' => 'private',
            'throw' => true,
        ],

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
