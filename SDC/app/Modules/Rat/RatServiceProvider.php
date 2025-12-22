<?php

namespace App\Modules\Rat;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RatRepositoryInterface::class,
            EloquentRatRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}

