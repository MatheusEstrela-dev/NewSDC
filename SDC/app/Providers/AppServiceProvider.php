<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Log\Context\Repository as ContextRepository;
use Laravel\Octane\Events\RequestReceived;

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

        $this->app->bind(\LLPhant\Chat\OpenAIChat::class, function () {
            $config = new \LLPhant\GeminiOpenAIConfig();
            $config->model = config('ai.drivers.gemini.model', 'gemini-2.0-flash');
            // api_key lida automaticamente de GEMINI_API_KEY pelo GeminiOpenAIConfig
            return new \LLPhant\Chat\OpenAIChat($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


        if (app()->environment('production')) {
            \URL::forceScheme('https');
        }

        // Spatie permissions: reset cache entre requests no Octane
        if (class_exists(\Laravel\Octane\Events\RequestReceived::class)) {
            $this->app['events']->listen(RequestReceived::class, function () {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            });
        }

        // Overrides para NativePHP / Mobile / Android
        // Detectamos pelo ambiente NativePHP ou se o SO for Linux (Android se reporta como Linux, mas checamos caminhos)
        if (env('NATIVEPHP_RUNNING') || env('NATIVE_PHP') || str_contains(strtolower(php_uname('a')), 'android') || str_contains(__DIR__, '/com.bifrost')) {
            config(['inertia.ssr.enabled' => false]);
            config(['octane.server' => null]);

            // NativePHP sempre usa assets do build, nunca do dev server.
            // Remove public/hot para evitar que o Laravel aponte para o Vite dev server.
            $hotFile = public_path('hot');
            if (file_exists($hotFile)) {
                @unlink($hotFile);
            }

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

            $dbOk = false;

            try {
                \DB::connection('mysql')->getPdo();
                \Log::info('NativePHP DB: MySQL connection SUCCESS');
                $dbOk = true;
            } catch (\Throwable $e) {
                \Log::warning('NativePHP DB: MySQL FAILED, falling back to SQLite', ['error' => $e->getMessage()]);

                try {
                    config(['database.default' => 'sqlite']);
                    config(['database.connections.sqlite.database' => storage_path('database.sqlite')]);

                    if (!file_exists(storage_path('database.sqlite'))) {
                        touch(storage_path('database.sqlite'));
                    }

                    // Test SQLite driver is available
                    \DB::connection('sqlite')->getPdo();

                    try {
                        if (!\Schema::hasTable('users')) {
                            \Artisan::call('migrate', ['--force' => true]);
                            \Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
                            \Log::info('NativePHP DB: SQLite migrated and seeded');
                        }
                    } catch (\Throwable $migError) {
                        \Log::error('NativePHP DB: SQLite migration failed', ['error' => $migError->getMessage()]);
                    }
                    $dbOk = true;
                } catch (\Throwable $sqliteError) {
                    // Both MySQL and SQLite failed — run without DB
                    // Use array driver to prevent any further DB errors
                    \Log::error('NativePHP DB: ALL DB drivers failed, running without database', [
                        'mysql_error' => $e->getMessage(),
                        'sqlite_error' => $sqliteError->getMessage(),
                    ]);
                    config(['database.default' => 'sqlite']);
                    config(['database.connections.sqlite.database' => ':memory:']);
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

        // Slow query detection - threshold em ms
        $slowQueryThreshold = config('app.env') === 'production' ? 1000 : 2000;

        DB::listen(function ($query) use ($slowQueryThreshold) {
            if ($query->time <= $slowQueryThreshold) {
                return;
            }

            try {
                $caller = $this->getQueryCaller();

                Log::channel('queries')->warning('Slow Query Detected', [
                    'sql'          => $query->sql,
                    'time_ms'      => $query->time,
                    'connection'   => $query->connection->getName(),
                    'url'          => app()->bound('request') ? request()->fullUrl() : null,
                    'user_id'      => app()->bound('auth') && auth()->check() ? auth()->id() : null,
                    'class'        => $caller['class'] ?? null,
                    'method'       => $caller['method'] ?? null,
                    'file'         => $caller['file'] ?? null,
                    'line'         => $caller['line'] ?? null,
                ]);

                if ($query->time > ($slowQueryThreshold * 2)) {
                    Log::channel('critical')->error('Critical Slow Query', [
                        'sql'     => $query->sql,
                        'time_ms' => $query->time,
                        'url'     => request()?->fullUrl(),
                    ]);
                }
            } catch (\Throwable $logError) {
                // Silently ignore
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

    private function getQueryCaller(): array
    {
        $basePath = base_path();
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            if (!str_starts_with($file, $basePath)) {
                continue;
            }
            if (str_contains($file, 'vendor/') || str_contains($file, 'vendor\\')) {
                continue;
            }
            if (str_contains($file, 'AppServiceProvider')) {
                continue;
            }
            $class = $frame['class'] ?? '';
            if (str_contains($class, 'Illuminate\\Database')) {
                continue;
            }

            $relative = str_replace([$basePath . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file);
            return [
                'class'     => $class ?: null,
                'method'    => $frame['function'] ?? null,
                'file_path' => $relative,
                'file'      => basename($file),
                'line'      => $frame['line'] ?? null,
            ];
        }

        return [];
    }
}
