<?php

namespace App\Providers;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Registra o driver de filesystem "azure" (Azure Blob Storage).
     *
     * Usado pelos discos de dominio (pae/compdec/exports) em producao, onde o
     * App Service roda com FS efemero (WEBSITES_ENABLE_APP_SERVICE_STORAGE=false)
     * e anexos gravados localmente seriam perdidos a cada restart/deploy.
     */
    public function boot(): void
    {
        Storage::extend('azure', function (Application $app, array $config): FilesystemAdapter {
            $containerClient = BlobServiceClient::fromConnectionString($config['connection_string'])
                ->getContainerClient($config['container']);

            // Azure Blob nao tem visibilidade por objeto: ignora setVisibility em vez
            // de lancar excecao (o default do adapter lancaria UnableToSetVisibility).
            $adapter = new AzureBlobStorageAdapter(
                $containerClient,
                (string) ($config['prefix'] ?? ''),
                null,
                AzureBlobStorageAdapter::ON_VISIBILITY_IGNORE,
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config,
            );
        });
    }
}
