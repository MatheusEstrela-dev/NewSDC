<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Log\Context\Repository as ContextRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Contracts\HierarchyServiceInterface::class,
            \App\Services\Auth\HierarchyService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


        if (app()->environment('production')) {
            \URL::forceScheme('https');
        }

        // Overrides para NativePHP / Mobile / Android
        // Detectamos pelo ambiente NativePHP ou se o SO for Linux (Android se reporta como Linux, mas checamos caminhos)
        if (env('NATIVEPHP_RUNNING') || env('NATIVE_PHP') || str_contains(strtolower(php_uname('a')), 'android') || str_contains(__DIR__, '/com.bifrost')) {
            config(['inertia.ssr.enabled' => false]);
            config(['octane.server' => null]);

            // Configura MySQL: aponta para o host (PC) com timeout curto
            // para não travar o boot do app se o DB não for alcançável
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => env('NATIVE_DB_HOST', '10.0.2.2'),
                'database.connections.mysql.port' => env('NATIVE_DB_PORT', '3307'),
                'database.connections.mysql.database' => env('DB_DATABASE', 'sdc'),
                'database.connections.mysql.username' => env('DB_USERNAME', 'sdc'),
                'database.connections.mysql.password' => env('DB_PASSWORD', 'secret'),
                'database.connections.mysql.options' => [
                    \PDO::ATTR_TIMEOUT => 3,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
            ]);

            try {
                \DB::connection('mysql')->getPdo();
                \Log::info('NativePHP DB: MySQL connection SUCCESS');
            } catch (\Throwable $e) {
                \Log::warning('NativePHP DB: MySQL FAILED, falling back to SQLite', ['error' => $e->getMessage()]);

                config(['database.default' => 'sqlite']);
                config(['database.connections.sqlite.database' => storage_path('database.sqlite')]);

                if (!file_exists(storage_path('database.sqlite'))) {
                    touch(storage_path('database.sqlite'));
                }

                try {
                    if (!\Schema::hasTable('users')) {
                        \Artisan::call('migrate', ['--force' => true]);
                        \Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
                        \Log::info('NativePHP DB: SQLite migrated and seeded');
                    }
                } catch (\Throwable $migError) {
                    \Log::error('NativePHP DB: SQLite migration failed', ['error' => $migError->getMessage()]);
                }
            }
        }

        if (app()->runningInConsole() === false) {
            $requestId = (string) Str::uuid();

            if (class_exists(\Illuminate\Support\Facades\Context::class)) {
                \Illuminate\Support\Facades\Context::add('request_id', $requestId);
                \Illuminate\Support\Facades\Context::add('environment', config('app.env'));
                \Illuminate\Support\Facades\Context::add('app_name', config('app.name'));
            } else {
                Log::withContext([
                    'request_id' => $requestId,
                    'environment' => config('app.env'),
                    'app_name' => config('app.name'),
                ]);
            }

            if (app()->bound('request')) {
                request()->headers->set('X-Request-ID', $requestId);
            }
        }

        DB::listen(function ($query) {
            $threshold = config('app.env') === 'production' ? 1000 : 2000;

            if ($query->time > $threshold) {
                Log::channel('queries')->warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'url' => app()->bound('request') ? request()->fullUrl() : null,
                    'method' => app()->bound('request') ? request()->method() : null,
                    'user_id' => auth()->id(),
                    'threshold_ms' => $threshold,
                    'severity' => $query->time > ($threshold * 2) ? 'critical' : 'warning',
                ]);

                if ($query->time > ($threshold * 2)) {
                    Log::channel('critical')->error('Critical Slow Query', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                        'url' => request()?->fullUrl(),
                    ]);
                }
            }
        });

        if (app()->runningInConsole()) {
            $commandId = (string) Str::uuid();

            if (class_exists(\Illuminate\Support\Facades\Context::class)) {
                \Illuminate\Support\Facades\Context::add('command_id', $commandId);
                \Illuminate\Support\Facades\Context::add('is_console', true);
            } else {
                Log::withContext([
                    'command_id' => $commandId,
                    'is_console' => true,
                ]);
            }
        }

        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Redefinição de Senha - SDC')
                ->view('emails.password_reset_simple', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                    'cpf_coordenador' => $notifiable->cpf ?? null,
                ]);
        });
    }
}
