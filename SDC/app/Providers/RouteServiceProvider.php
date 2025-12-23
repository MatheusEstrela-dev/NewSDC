<?php

namespace App\Providers;

use App\Models\Empreendimento;
use App\Models\Entrada;
use App\Models\Protocolo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        /**
         * Bindings explícitos para resources que ainda não têm persistência real.
         *
         * Isso permite que `authorizeResource()` receba um model (objeto) e o Gate
         * consiga resolver a Policy correta, mesmo sem tabela/DB no módulo.
         */
        Route::bind('empreendimento', fn ($value) => new Empreendimento(['id' => (int) $value]));
        Route::bind('protocolo', fn ($value) => new Protocolo(['id' => (int) $value]));
        Route::bind('entrada', fn ($value) => new Entrada(['id' => (int) $value]));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
