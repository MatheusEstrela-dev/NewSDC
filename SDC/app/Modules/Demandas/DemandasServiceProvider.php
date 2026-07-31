<?php

declare(strict_types=1);

namespace App\Modules\Demandas;

use App\Modules\Demandas\Models\Task;
use App\Modules\Demandas\Observers\TaskNotificacaoObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Demandas
 */
class DemandasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TaskService is auto-resolved by Laravel's container
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/demandas.php

        // Avisos de atribuicao e mudanca de status. O observer so despacha job,
        // entao nao entra no custo da requisicao que salvou a demanda.
        Task::observe(TaskNotificacaoObserver::class);
    }
}
