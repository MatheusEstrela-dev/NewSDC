<?php

declare(strict_types=1);

namespace App\Modules\Demandas;

use App\Modules\Demandas\Console\ImportarLegadoCommand;
use App\Modules\Demandas\Console\SlaVerificadorCommand;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Importacao\Etapas\ImportarAnexos;
use App\Modules\Demandas\Importacao\Etapas\ImportarCatalogo;
use App\Modules\Demandas\Importacao\Etapas\ImportarChamados;
use App\Modules\Demandas\Importacao\Etapas\ImportarComentarios;
use App\Modules\Demandas\Importacao\Etapas\ImportarHistorico;
use App\Modules\Demandas\Importacao\Etapas\ImportarUsuarios;
use App\Modules\Demandas\Infrastructure\Persistence\EloquentDemandaRepository;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Observers\DemandaNotificacaoObserver;
use App\Modules\Demandas\Observers\DemandaTempoRealObserver;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Demandas
 */
class DemandasServiceProvider extends ServiceProvider
{
    public array $bindings = [
        DemandaRepository::class => EloquentDemandaRepository::class,
    ];

    public function register(): void
    {
        $this->app->scoped(ContextoImportacao::class);

        // Ordem importa: cada etapa depende do mapa gravado pela anterior.
        $this->app->tag([
            ImportarUsuarios::class,
            ImportarCatalogo::class,
            ImportarChamados::class,
            ImportarHistorico::class,
            ImportarComentarios::class,
            ImportarAnexos::class,
        ], 'demandas.importacao.etapas');
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/demandas.php

        // Avisos de atribuicao e mudanca de status. O observer so despacha job,
        // entao nao entra no custo da requisicao que salvou a demanda.
        Demanda::observe(DemandaNotificacaoObserver::class);

        // Tempo real: avisa listagens abertas que uma demanda mudou.
        Demanda::observe(DemandaTempoRealObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SlaVerificadorCommand::class, ImportarLegadoCommand::class]);
        }
    }
}
