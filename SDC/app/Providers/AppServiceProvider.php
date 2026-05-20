<?php

namespace App\Providers;

use App\Database\ConnectionSemaphore;
use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
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

        $this->app->singleton(ConnectionSemaphore::class, function ($app) {
            $cfg = $app['config']->get('resilience.db');
            return new ConnectionSemaphore(
                Redis::connection()->client(),
                limit: $cfg['max_concurrent'],
                waitMs: $cfg['acquire_poll_ms'],
                maxWaitMs: $cfg['acquire_wait_ms'],
            );
        });

        $this->app->singleton(DatabaseCircuitBreaker::class, function ($app) {
            $cfg = $app['config']->get('resilience.db.circuit_breaker');
            return new DatabaseCircuitBreaker(
                timeoutThreshold: $cfg['timeout_count_threshold'],
                windowSeconds: $cfg['window_seconds'],
                resetSeconds: $cfg['reset_timeout_seconds'],
            );
        });

        $this->app->bind(\LLPhant\Chat\OpenAIChat::class, function () {
            $config = new \LLPhant\GeminiOpenAIConfig();
            $config->model = config('ai.drivers.gemini.model', 'gemini-2.0-flash');
            $config->modelOptions = ['temperature' => 0.1];

            // FrankenPHP embedded PHP 8.5: stream_socket_client ssl:// fails silently (errno=0).
            // Bypass by forcing CurlHandler — Guzzle's default wrapStreaming() proxy routes
            // stream=>true requests to StreamHandler, which uses the broken SSL wrapper.
            $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\CurlHandler());
            $stack->push(\LLPhant\Chat\OpenAIResponseErrorsProcessor::createResponseModifier());

            $guzzle = new \GuzzleHttp\Client([
                'handler'         => $stack,
                'timeout'         => 300.0,
                'connect_timeout' => 30.0,
                'read_timeout'    => 300.0,
            ]);

            $config->client = \OpenAI::factory()
                ->withApiKey($config->apiKey)
                ->withBaseUri($config->url)
                ->withHttpClient($guzzle)
                ->make();

            return new \LLPhant\Chat\OpenAIChat($config);
        });

        $this->app->bind(\App\Core\IA\Embeddings\GeminiEmbeddingGenerator::class, function () {
            return new \App\Core\IA\Embeddings\GeminiEmbeddingGenerator();
        });

        $this->app->bind(\LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore::class, function () {
            $connectionParams = [
                'driver'   => 'pdo_pgsql',
                'host'     => env('DB_PGSQL_HOST', '127.0.0.1'),
                'port'     => (int) env('DB_PGSQL_PORT', 5432),
                'dbname'   => env('DB_PGSQL_DATABASE', env('DB_DATABASE')),
                'user'     => env('DB_PGSQL_USERNAME', env('DB_USERNAME')),
                'password' => env('DB_PGSQL_PASSWORD', env('DB_PASSWORD', '')),
            ];

            $doctrineConfig = new \Doctrine\ORM\Configuration();
            $doctrineConfig->setMetadataDriverImpl(
                new \Doctrine\ORM\Mapping\Driver\AttributeDriver([
                    base_path('core/IA/Embeddings'),
                    base_path('vendor/theodo-group/llphant/src/Embeddings/VectorStores/Doctrine'),
                ])
            );
            $doctrineConfig->setProxyDir(storage_path('app/doctrine/proxies'));
            $doctrineConfig->setProxyNamespace('App\Core\IA\DoctrineProxies');
            $doctrineConfig->setAutoGenerateProxyClasses(true);

            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
            $em = new \Doctrine\ORM\EntityManager($connection, $doctrineConfig);

            return new \LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore(
                $em,
                \App\Core\IA\Embeddings\DecEmbeddingEntity::class
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->removeViteHotFileWhenBuildAssetsShouldBeUsed();

        // Spatie permissions: reset cache entre requests no Octane
        if (class_exists(\Laravel\Octane\Events\RequestReceived::class)) {
            $this->app['events']->listen(RequestReceived::class, function () {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            });
        }

        // Overrides para NativePHP / Mobile / Android
        $isNative = env('NATIVEPHP_RUNNING') || env('NATIVE_PHP') || str_contains(strtolower(php_uname('a')), 'android') || str_contains(__DIR__, '/com.bifrost');
        $isDocker = file_exists('/.dockerenv');

        if ($isNative && !$isDocker) {
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
                DB::connection('mysql')->getPdo();
                Log::info('NativePHP DB: MySQL connection SUCCESS');
                $dbOk = true;
            } catch (\Throwable $e) {
                Log::warning('NativePHP DB: MySQL FAILED, falling back to SQLite', ['error' => $e->getMessage()]);

                try {
                    config(['database.default' => 'sqlite']);
                    config(['database.connections.sqlite.database' => storage_path('database.sqlite')]);

                    if (!file_exists(storage_path('database.sqlite'))) {
                        touch(storage_path('database.sqlite'));
                    }

                    // Test SQLite driver is available
                    DB::connection('mysql')->getPdo();

                    try {
                        if (!Schema::hasTable('users')) {
                            Artisan::call('migrate', ['--force' => true]);
                            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
                            Log::info('NativePHP DB: SQLite migrated and seeded');
                        }
                    } catch (\Throwable $migError) {
                        Log::error('NativePHP DB: SQLite migration failed', ['error' => $migError->getMessage()]);
                    }
                    $dbOk = true;
                } catch (\Throwable $sqliteError) {
                    // Both MySQL and SQLite failed — run without DB
                    // Use array driver to prevent any further DB errors
                    Log::error('NativePHP DB: ALL DB drivers failed, running without database', [
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
        $circuitBreaker = $this->app->make(DatabaseCircuitBreaker::class);

        DB::listen(function ($query) use ($slowQueryThreshold, $circuitBreaker) {
            // Sinaliza timeout suspeito ao circuit breaker (>30s).
            if ($query->time > 30000) {
                $circuitBreaker->recordTimeout();
            }

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
            // URL opaca: encripta {token,email} para nao expor o token ou o email na URL.
            // Decriptado em NewPasswordController::create. Validade real continua via
            // password_reset_tokens (15min, config/auth.php).
            $payload = \Illuminate\Support\Facades\Crypt::encryptString(json_encode([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], JSON_THROW_ON_ERROR));

            $resetUrl = url('/reset-password/' . $payload);

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Redefinição de Senha - SDC')
                ->view('emails.password_reset_simple', [
                    'resetUrl' => $resetUrl,
                    'cpf_coordenador' => $notifiable->cpf ?? null,
                ])
                ->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) {
                    // Inline image via CID — Outlook/Gmail nao bloqueiam (parte do email).
                    // Logo so-hexagono; "DEFESA CIVIL" renderiza em HTML branco ao lado.
                    $logoPath = public_path('imgs/logo_dc.png');
                    if (is_file($logoPath)) {
                        $message->embedFromPath($logoPath, 'logo-cedec', 'image/png');
                    }
                });
        });
    }

    private function removeViteHotFileWhenBuildAssetsShouldBeUsed(): void
    {
        $buildManifestExists = file_exists(public_path('build/manifest.json'));
        $shouldUseBuildAssets = !app()->environment('local') || $buildManifestExists;

        if (!$shouldUseBuildAssets) {
            return;
        }

        $hotFile = public_path('hot');
        if (!file_exists($hotFile)) {
            return;
        }

        @unlink($hotFile);
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
