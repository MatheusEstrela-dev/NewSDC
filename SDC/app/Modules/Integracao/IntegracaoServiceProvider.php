<?php

declare(strict_types=1);

namespace App\Modules\Integracao;

use App\Modules\Integracao\Console\PodarCommand;
use App\Modules\Integracao\Console\ReenfileirarCommand;
use App\Modules\Integracao\Support\CaixaDeEntrada;
use App\Modules\Integracao\Support\CatalogoDeOperacoes;
use App\Modules\Integracao\Support\ProcessadorDeEntrada;
use App\Modules\Integracao\Support\RegistroDeAplicacoes;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Entrada assincrona da API externa: middleware integracao.entrada grava em
 * entrada.mensagens (sdc_powerbi) e AplicarEntradaJob aplica no sdc.
 * Inerte sem DB_POWERBI_CONNECTION: as rotas seguem sincronas.
 */
class IntegracaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogoDeOperacoes::class);
        $this->app->singleton(CaixaDeEntrada::class);
        $this->app->singleton(RegistroDeAplicacoes::class);
        $this->app->singleton(ProcessadorDeEntrada::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ReenfileirarCommand::class, PodarCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (! ConexaoPowerBi::ativa()) {
                return;
            }

            $schedule->command('integracao:reenfileirar')->everyMinute()
                ->onOneServer()->withoutOverlapping(5);

            $schedule->command('integracao:podar')->dailyAt('03:45')
                ->timezone('America/Sao_Paulo')->onOneServer()->withoutOverlapping(60);
        });
    }
}
