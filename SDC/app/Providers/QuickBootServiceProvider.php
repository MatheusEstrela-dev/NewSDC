<?php

namespace App\Providers;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;

class QuickBootServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register auth facade before any provider tries to use it
        $this->app->singleton('auth', function ($app) {
            return new AuthManager($app);
        });

        // Also add as class alias to prevent auto-wiring failures
        $this->app->alias('auth', AuthManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
