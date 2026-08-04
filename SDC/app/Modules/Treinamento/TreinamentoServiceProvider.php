<?php

declare(strict_types=1);

namespace App\Modules\Treinamento;

use Illuminate\Support\ServiceProvider;

class TreinamentoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/treinamento.php
    }
}
