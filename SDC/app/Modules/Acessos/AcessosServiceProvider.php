<?php

declare(strict_types=1);

namespace App\Modules\Acessos;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\Infrastructure\DiretorioDesligado;
use App\Modules\Acessos\Infrastructure\FakeDiretorioCorporativo;
use App\Modules\Acessos\Infrastructure\LdapDiretorioCorporativo;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AcessosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton: com o fake, quem semeia e quem executa enxergam a mesma instancia.
        $this->app->singleton(FakeDiretorioCorporativo::class);

        // Singleton: no worker o adaptador LDAP reaproveita a conexao entre jobs.
        $this->app->singleton(DiretorioCorporativo::class, fn (Application $app) => match (config('acessos.diretorio.driver')) {
            'ldap' => $app->make(LdapDiretorioCorporativo::class),
            'fake' => $app->isProduction() ? $this->fakeRecusadoEmProducao() : $app->make(FakeDiretorioCorporativo::class),
            default => new DiretorioDesligado(),
        });
    }

    /** O fake aceita qualquer acao sem AD: em producao vale o desligado. */
    private function fakeRecusadoEmProducao(): DiretorioDesligado
    {
        Log::warning('Diretorio: driver fake recusado em producao; usando o driver desligado.');

        return new DiretorioDesligado();
    }
}
