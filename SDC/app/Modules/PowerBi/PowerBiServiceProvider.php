<?php

declare(strict_types=1);

namespace App\Modules\PowerBi;

use App\Modules\PowerBi\Console\MaterializarCommand;
use App\Modules\PowerBi\Console\ReconciliarCommand;
use App\Modules\PowerBi\Jobs\MaterializarModuloJob;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\ExecutorMaterializacao;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\Reconciliador;
use App\Modules\PowerBi\Support\RegistroDeExecucoes;
use App\Modules\PowerBi\Support\VerificadorParidade;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Saida de BI: materializa tabelas bi_* no sdc_powerbi e as serve por
 * GET /api/v1/bi/{modulo}/{tabela}. Inerte sem DB_POWERBI_CONNECTION.
 */
class PowerBiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogoBi::class);
        $this->app->singleton(GravadorBi::class);
        $this->app->singleton(RegistroDeExecucoes::class);
        $this->app->singleton(ExecutorMaterializacao::class);
        $this->app->singleton(Reconciliador::class);
        $this->app->singleton(VerificadorParidade::class);
    }

    public function boot(): void
    {
        // 60/min por token (cai no usuario e no IP quando a sessao nao tem token).
        RateLimiter::for('bi-leitura', function (Request $request): Limit {
            $token = $request->user()?->currentAccessToken();
            $quem = $token?->id ?? $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('powerbi.api.rate_por_minuto', 60))->by('bi-leitura:'.$quem);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([MaterializarCommand::class, ReconciliarCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (! ConexaoPowerBi::ativa()) {
                return;
            }

            // Um job por modulo; o lock do executor impede sobreposicao.
            $schedule->call(function (): void {
                foreach (app(CatalogoBi::class)->modulos() as $modulo) {
                    MaterializarModuloJob::dispatch($modulo)->onQueue('powerbi');
                }
            })->name('powerbi:materializar')->everyTenMinutes()->onOneServer()->withoutOverlapping(9);

            $schedule->command('powerbi:reconciliar')->dailyAt('02:30')
                ->timezone('America/Sao_Paulo')->onOneServer()->withoutOverlapping(120);
        });
    }
}
