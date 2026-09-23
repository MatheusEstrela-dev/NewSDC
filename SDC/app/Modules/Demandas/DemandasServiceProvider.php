<?php

declare(strict_types=1);

namespace App\Modules\Demandas;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Infrastructure\Persistence\EloquentDemandaRepository;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Observers\DemandaNotificacaoObserver;
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
        // Outros bindings resolvidos pelo container
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/demandas.php

        // Avisos de atribuicao e mudanca de status. O observer so despacha job,
        // entao nao entra no custo da requisicao que salvou a demanda.
        Demanda::observe(DemandaNotificacaoObserver::class);
    }
}
