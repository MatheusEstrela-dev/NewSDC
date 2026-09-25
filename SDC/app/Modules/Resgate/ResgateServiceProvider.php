<?php

declare(strict_types=1);

namespace App\Modules\Resgate;

use App\Modules\Resgate\Contracts\SaldoResgatavel;
use App\Modules\Resgate\Services\CalcularCarteira;
use Illuminate\Support\ServiceProvider;

/**
 * Modulo Resgate: troca de pontos por servicos, adesoes e bens.
 * Plano: docs/superpowers/plans/2026-09-25-resgate-pontos-catalogo.md
 *
 * Fase 1: carteira somente leitura. Nenhuma rota escreve.
 */
final class ResgateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stateless: singleton seguro sob Octane.
        $this->app->singleton(SaldoResgatavel::class, CalcularCarteira::class);
    }
}
