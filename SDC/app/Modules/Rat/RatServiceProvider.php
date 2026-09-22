<?php

declare(strict_types=1);

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Rat\Config\RatActionsConfig;
use App\Modules\Rat\Domain\Events\RegistroCompletoV1;
use App\Modules\Rat\Domain\Events\RelatorioFinalizadoV1;
use App\Modules\Rat\Domain\Events\VistoriaValidadaV1;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use App\Modules\Rat\Services\RatAnexoService;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatHistoricoService;
use App\Modules\Rat\Services\RatOcorrenciaService;
use App\Modules\Rat\Services\RatProtocoloService;
use App\Modules\Rat\Services\RatRelatoService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RatProtocoloService::class);
        $this->app->singleton(RatWriteService::class);
        $this->app->singleton(RatExportService::class);
        $this->app->singleton(RatAttachmentService::class);
        $this->app->singleton(RatAnexoService::class);
        $this->app->singleton(RatOcorrenciaService::class);
        $this->app->singleton(RatHistoricoService::class);
        $this->app->singleton(RatRelatoService::class);

        // Mapa evento -> classe usado por OutboxDispatcher::reidratar(). Sem
        // isto, persist() grava normalmente mas o worker nunca consegue
        // despachar: os eventos ficam presos em outbox_events.
        $this->app->extend(OutboxDispatcher::class, function (OutboxDispatcher $dispatcher) {
            return $dispatcher
                ->register('rat.ocorrencia.registrada', 1, RegistroCompletoV1::class)
                ->register('rat.relatorio.finalizado', 1, RelatorioFinalizadoV1::class)
                // Registrada para que o replay do marco funcione quando o
                // fluxo de aceite existir. Hoje nenhum ponto do RAT emite este
                // evento: nao ha coluna de validacao/validador na vistoria.
                ->register('rat.vistoria.validada', 1, VistoriaValidadaV1::class);
        });
    }

    public function boot(): void
    {
        $this->registerModuleActions();

        // A trilha de acoes do RAT (edicao, situacao, anexo, exclusao) nao e registrada
        // aqui: vem dos traits TrilhaDeAcoes em RatOcorrencia e TrilhaNoProtocoloPai em
        // RatRelato/RatAnexo. O antigo RatOcorrenciaNotificacaoObserver cobria somente
        // a virada para Finalizado, e com o numero interno no texto.
    }

    private function registerModuleActions(): void
    {
        if ($this->app->bound(ActionConfigService::class)) {
            $this->app->make(ActionConfigService::class)
                ->registerModule(new RatActionsConfig());
        }
    }
}
