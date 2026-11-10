<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas;

use App\Modules\NotasTecnicas\Console\ImportarGuiasUsoCommand;
use App\Modules\NotasTecnicas\Console\ReindexarNotasTecnicasCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Modulo Notas Tecnicas. As rotas ficam em routes/modules/notas-tecnicas.php,
 * incluido no grupo auth de routes/web.php -- NAO usar loadRoutesFrom() aqui:
 * registraria as rotas sem os middlewares web/auth.
 */
class NotasTecnicasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReindexarNotasTecnicasCommand::class,
                ImportarGuiasUsoCommand::class,
            ]);
        }
    }
}
