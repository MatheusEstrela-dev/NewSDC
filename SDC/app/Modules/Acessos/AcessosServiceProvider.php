<?php

declare(strict_types=1);

namespace App\Modules\Acessos;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\Infrastructure\HttpDiretorioCorporativo;
use Illuminate\Support\ServiceProvider;

class AcessosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DiretorioCorporativo::class, HttpDiretorioCorporativo::class);
    }
}
