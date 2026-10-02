<?php

declare(strict_types=1);

namespace App\Modules\Acessos;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\Infrastructure\DiretorioDesligado;
use App\Modules\Acessos\Infrastructure\FakeDiretorioCorporativo;
use Illuminate\Support\ServiceProvider;

class AcessosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton: com o fake, quem semeia e quem executa enxergam a mesma instancia.
        $this->app->singleton(FakeDiretorioCorporativo::class);

        $this->app->singleton(DiretorioCorporativo::class, fn ($app) => match (config('acessos.diretorio.driver')) {
            'fake' => $app->make(FakeDiretorioCorporativo::class),
            default => new DiretorioDesligado(),
        });
    }
}
