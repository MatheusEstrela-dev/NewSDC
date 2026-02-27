<?php

namespace App\Modules\Suporte;

use App\Modules\Suporte\Services\SupportTicketService;
use Illuminate\Support\ServiceProvider;

class SuporteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupportTicketService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/suporte.php
    }
}
