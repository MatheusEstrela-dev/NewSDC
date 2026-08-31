<?php

namespace App\Providers;

use App\Models\Empreendimento;
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
            // Bypass gated por token (apenas para load-test de capacidade): quando
            // LOADTEST_BYPASS_TOKEN esta setado E o header X-Loadtest-Bypass bate,
            // libera o limite para medir o teto real do pool/workers a partir de
            // uma fonte unica. Sem o env (default) o comportamento e inalterado.
            $bypass = (string) env('LOADTEST_BYPASS_TOKEN', '');
            if ($bypass !== '' && hash_equals($bypass, (string) $request->header('X-Loadtest-Bypass', ''))) {
                return Limit::none();
            }

            return Limit::perMinute((int) env('RATE_LIMIT_API_PERMIN', 60))->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $identifier = $request->input('cpf') ?: $request->input('email') ?: $request->ip();

            return Limit::perMinute(5)->by(strtolower((string) $identifier) . '|' . $request->ip());
        });

        // Teto de abuso do cadastro publico do Portal de Treinamentos. Folgado
        // de proposito: e a UNICA tela de cadastro publico do sistema e ela e
        // acessada de rede institucional com NAT, onde muitos cidadaos saem pelo
        // mesmo IP. O limite que o usuario legitimo enxerga e o de dentro do
        // RegisterCidadaoRequest, que devolve mensagem inline em vez de 429 crua;
        // este aqui e a barreira que sobra pra automacao.
        RateLimiter::for('portal-registro', function (Request $request) {
            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perHour(60)->by($request->ip()),
            ];
        });

        RateLimiter::for('default', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('premium', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('enterprise', function (Request $request) {
            return Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(500)->by($request->ip());
        });

        /**
         * Bindings explícitos para resources que ainda não têm persistência real.
         *
         * Isso permite que `authorizeResource()` receba um model (objeto) e o Gate
         * consiga resolver a Policy correta, mesmo sem tabela/DB no módulo.
         */
        Route::bind('empreendimento', fn ($value) => new Empreendimento(['id' => (int) $value]));
        Route::bind('protocolo', fn ($value) => new Protocolo(['id' => (int) $value]));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
