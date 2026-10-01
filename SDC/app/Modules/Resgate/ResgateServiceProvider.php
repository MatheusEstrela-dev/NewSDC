<?php

declare(strict_types=1);

namespace App\Modules\Resgate;

use App\Modules\Resgate\Console\ExpirarReservasCommand;
use App\Modules\Resgate\Console\VerificarTrilhasCommand;
use App\Modules\Resgate\Contracts\SaldoResgatavel;
use App\Modules\Resgate\Services\CalcularCarteira;
use Illuminate\Support\ServiceProvider;

/**
 * Modulo Resgate: troca de pontos por servicos, adesoes e bens.
 * Plano: docs/superpowers/plans/2026-09-25-resgate-pontos-catalogo.md
 *
 * Fase 1 carteira, Fase 2 catalogo, Fase 3 pedido reservado ate a CEDEC decidir.
 */
final class ResgateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stateless: singleton seguro sob Octane.
        $this->app->singleton(SaldoResgatavel::class, CalcularCarteira::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExpirarReservasCommand::class, VerificarTrilhasCommand::class]);
        }
    }
}
