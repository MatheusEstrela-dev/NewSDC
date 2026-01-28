<?php

namespace App\Modules\Suporte;

use App\Modules\Suporte\Domain\Repositories\SupportTicketRepositoryInterface;
use App\Modules\Suporte\Infrastructure\Persistence\Repositories\EloquentSupportTicketRepository;
use Illuminate\Support\ServiceProvider;

class SuporteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupportTicketRepositoryInterface::class,
            EloquentSupportTicketRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
