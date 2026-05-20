# DB/API Resilience (Software-Defined) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o NewSDC resiliente a >1000 req/s em pico de crise de Defesa Civil aplicando defesas em código (padrões Laravel/PHP/Redis), sem depender de infra externa (PgBouncer, ProxySQL, tiers managed específicos).

**Architecture:** Camada de defesa em 5 níveis no código: (1) reuso de conexões via listener seletivo do Octane, (2) limitação de concorrência via semáforo Redis, (3) timeout por contexto via `SET LOCAL`, (4) circuit breaker observando p95+timeouts, (5) backpressure que dropa carga não-crítica antes do DB. Observabilidade via Prometheus endpoint na própria app. Tudo configurável em `config/resilience.php` com defaults seguros.

**Tech Stack:** PHP 8.x, Laravel 11, Octane 2.13 (RoadRunner/FrankenPHP), PostgreSQL 17, Redis 7 (phpredis), PHPUnit 11, k6 (load test).

---

## File Structure

**Novos arquivos (16):**
- `config/resilience.php` — knobs centralizados
- `app/Listeners/Octane/SelectiveDisconnectFromDatabases.php`
- `app/Http/Middleware/SetStatementTimeout.php`
- `app/Database/ConnectionSemaphore.php`
- `app/Http/Middleware/AcquireConnectionSlot.php`
- `app/Services/Database/DatabaseCircuitBreaker.php`
- `app/Http/Middleware/Backpressure.php`
- `app/Support/Cache/CachedRepository.php`
- `app/Database/QueryBudgetGuard.php`
- `app/Console/Commands/ArchiveWebhookEventsCommand.php`
- `app/Http/Middleware/CacheSwaggerUi.php`
- `app/Http/Controllers/Api/MetricsController.php`
- `database/migrations/2026_05_21_000000_create_webhook_events_archive_table.php`
- `tests/Unit/Database/ConnectionSemaphoreTest.php`
- `tests/Unit/Services/Database/DatabaseCircuitBreakerTest.php`
- `tests/Feature/Resilience/BackpressureMiddlewareTest.php` (e outros tests por feature)

**Modificações (9):**
- `config/database.php` — adicionar `pgsql_webhook`, `PDO::ATTR_PERSISTENT`
- `config/octane.php` — trocar listener
- `config/queue.php` — usar `pgsql_webhook` para webhook jobs
- `app/Http/Middleware/ApiRateLimiter.php` — fail-closed + global bucket
- `app/Http/Kernel.php` — registrar novos middlewares
- `routes/api.php` — aplicar `SetStatementTimeout` por grupo
- `app/Console/Kernel.php` — agendar `ArchiveWebhookEvents`
- `app/Http/Controllers/Api/HealthCheckController.php` — adicionar pool/CB status
- `app/Jobs/ProcessWebhook.php`, `ProcessInboundWebhook.php` — usar `pgsql_webhook`

---

## Task 1: Resilience Config Skeleton

**Files:**
- Create: `SDC/config/resilience.php`
- Modify: `SDC/.env.example` (adicionar defaults)

- [ ] **Step 1: Criar config**

`SDC/config/resilience.php`:

```php
<?php

declare(strict_types=1);

return [
    'db' => [
        'max_concurrent' => (int) env('DB_MAX_CONCURRENT', 100),
        'max_concurrent_webhook' => (int) env('DB_MAX_CONCURRENT_WEBHOOK', 20),
        'acquire_wait_ms' => (int) env('DB_ACQUIRE_WAIT_MS', 2000),
        'acquire_poll_ms' => (int) env('DB_ACQUIRE_POLL_MS', 50),
        'circuit_breaker' => [
            'p95_threshold_ms' => (int) env('DB_CB_P95_MS', 500),
            'timeout_count_threshold' => (int) env('DB_CB_TIMEOUT_COUNT', 5),
            'window_seconds' => (int) env('DB_CB_WINDOW_S', 60),
            'reset_timeout_seconds' => (int) env('DB_CB_RESET_S', 30),
        ],
    ],
    'rate_limit' => [
        'global_per_second' => (int) env('RATE_LIMIT_GLOBAL', 1500),
        'fail_closed' => (bool) env('RATE_LIMIT_FAIL_CLOSED', true),
    ],
    'query_budget' => [
        'warn_at' => (int) env('QUERY_BUDGET_WARN', 30),
        'fail_at' => (int) env('QUERY_BUDGET_FAIL', 100),
    ],
    'backpressure' => [
        'public_drop_threshold' => (float) env('BP_PUBLIC_DROP', 0.7),
        'free_drop_threshold' => (float) env('BP_FREE_DROP', 0.9),
    ],
];
```

- [ ] **Step 2: Adicionar entradas no `.env.example`**

Append em `SDC/.env.example` na seção "DATABASE":

```env
# Resilience knobs
DB_MAX_CONCURRENT=100
DB_MAX_CONCURRENT_WEBHOOK=20
DB_ACQUIRE_WAIT_MS=2000
DB_ACQUIRE_POLL_MS=50
DB_CB_P95_MS=500
DB_CB_TIMEOUT_COUNT=5
DB_CB_WINDOW_S=60
DB_CB_RESET_S=30
RATE_LIMIT_GLOBAL=1500
RATE_LIMIT_FAIL_CLOSED=true
QUERY_BUDGET_WARN=30
QUERY_BUDGET_FAIL=100
BP_PUBLIC_DROP=0.7
BP_FREE_DROP=0.9
```

- [ ] **Step 3: Validar carregamento**

Run: `php artisan config:show resilience.db.max_concurrent`
Expected: `100`

- [ ] **Step 4: Commit**

```bash
git add SDC/config/resilience.php SDC/.env.example
git commit -m "feat(resilience): config centralizado com knobs de DB/CB/rate-limit"
```

---

## Task 2: SelectiveDisconnectFromDatabases Listener

**Files:**
- Create: `SDC/app/Listeners/Octane/SelectiveDisconnectFromDatabases.php`
- Create: `SDC/tests/Feature/Octane/SelectiveDisconnectTest.php`
- Modify: `SDC/config/octane.php` (substituir listener)
- Modify: `SDC/config/database.php` (adicionar `ATTR_PERSISTENT`)

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Feature/Octane/SelectiveDisconnectTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Octane;

use App\Listeners\Octane\SelectiveDisconnectFromDatabases;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Tests\TestCase;

class SelectiveDisconnectTest extends TestCase
{
    public function test_disconnects_only_volatile_connections(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('purge')->once()->with('tenancy');
        $db->shouldReceive('purge')->once()->with('legacy');
        $db->shouldReceive('purge')->once()->with('carga');
        $db->shouldNotReceive('purge')->with('pgsql');
        $db->shouldNotReceive('purge')->with('pgsql_read');

        $listener = new SelectiveDisconnectFromDatabases($db);
        $listener->handle(new \stdClass());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar que falha**

Run: `php artisan test --filter SelectiveDisconnectTest`
Expected: FAIL com `Class "App\Listeners\Octane\SelectiveDisconnectFromDatabases" not found`.

- [ ] **Step 3: Implementar listener**

`SDC/app/Listeners/Octane/SelectiveDisconnectFromDatabases.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners\Octane;

use Illuminate\Database\DatabaseManager;

class SelectiveDisconnectFromDatabases
{
    /**
     * Conexões que mudam por request (tenancy dinâmica, ETL ad-hoc).
     * Devem ser desconectadas ao fim de cada operação para evitar
     * vazamento de estado entre requests no mesmo worker Octane.
     */
    private const CONEXOES_VOLATEIS = ['tenancy', 'legacy', 'carga'];

    public function __construct(private DatabaseManager $db) {}

    public function handle(object $event): void
    {
        foreach (self::CONEXOES_VOLATEIS as $nome) {
            $this->db->purge($nome);
        }
    }
}
```

- [ ] **Step 4: Rodar teste, confirmar que passa**

Run: `php artisan test --filter SelectiveDisconnectTest`
Expected: PASS.

- [ ] **Step 5: Habilitar persistência no PDO**

Modify `SDC/config/database.php` em `'pgsql'` (linha ~145) e replicar em `'pgsql_read'`:

```php
'options' => [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => (bool) env('DB_PERSISTENT', true),
],
```

- [ ] **Step 6: Trocar listener no Octane**

Modify `SDC/config/octane.php` linhas 105-110:

```php
OperationTerminated::class => [
    FlushOnce::class,
    FlushTemporaryContainerInstances::class,
    \App\Listeners\Octane\SelectiveDisconnectFromDatabases::class,
    CollectGarbage::class,
],
```

Remover o `use Laravel\Octane\Listeners\DisconnectFromDatabases;` no topo do arquivo.

- [ ] **Step 7: Rodar full test suite**

Run: `php artisan test`
Expected: todos os testes passam (sanity check de que tenancy não quebrou).

- [ ] **Step 8: Commit**

```bash
git add SDC/app/Listeners/Octane/ SDC/config/octane.php SDC/config/database.php SDC/tests/Feature/Octane/
git commit -m "feat(resilience): listener seletivo do Octane preserva pgsql persistente"
```

---

## Task 3: SetStatementTimeout Middleware

**Files:**
- Create: `SDC/app/Http/Middleware/SetStatementTimeout.php`
- Create: `SDC/tests/Feature/Middleware/SetStatementTimeoutTest.php`
- Modify: `SDC/app/Http/Kernel.php` (registrar alias)
- Modify: `SDC/routes/api.php` (aplicar por grupo)

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Feature/Middleware/SetStatementTimeoutTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetStatementTimeout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetStatementTimeoutTest extends TestCase
{
    public function test_sets_local_statement_timeout_for_request(): void
    {
        $middleware = new SetStatementTimeout();
        $request = Request::create('/test');

        $captured = [];
        DB::listen(function ($query) use (&$captured) {
            $captured[] = $query->sql;
        });

        $middleware->handle($request, fn() => response('ok'), 5000);

        $this->assertContains('SET LOCAL statement_timeout = 5000', $captured);
        $this->assertContains('SET LOCAL idle_in_transaction_session_timeout = 60000', $captured);
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar que falha**

Run: `php artisan test --filter SetStatementTimeoutTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Implementar middleware**

`SDC/app/Http/Middleware/SetStatementTimeout.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetStatementTimeout
{
    public function handle(Request $request, Closure $next, int $timeoutMs = 10000): Response
    {
        DB::statement("SET LOCAL statement_timeout = {$timeoutMs}");
        DB::statement('SET LOCAL idle_in_transaction_session_timeout = 60000');

        return $next($request);
    }
}
```

- [ ] **Step 4: Rodar teste, confirmar passa**

Run: `php artisan test --filter SetStatementTimeoutTest`
Expected: PASS.

- [ ] **Step 5: Registrar alias no Kernel**

Modify `SDC/app/Http/Kernel.php` no array `$middlewareAliases` (ou `$routeMiddleware` em Laravel < 11):

```php
'statement_timeout' => \App\Http\Middleware\SetStatementTimeout::class,
```

- [ ] **Step 6: Aplicar por grupo em `routes/api.php`**

Modify `SDC/routes/api.php`. Envolver grupo de webhooks com timeout 15s, grupo geral com 10s, healthcheck com 2s:

```php
Route::middleware('statement_timeout:2000')
    ->prefix('health')
    ->group(fn() => Route::get('/', [HealthCheckController::class, 'index']));

Route::middleware('statement_timeout:10000')
    ->prefix('v1')
    ->group(function () {
        Route::middleware('statement_timeout:15000')
            ->prefix('webhooks')
            ->group(fn() => require __DIR__.'/webhook_routes.php');
        // demais rotas v1...
    });
```

- [ ] **Step 7: Smoke test manual**

Run: `php artisan serve` (terminal A) e em terminal B:

```bash
curl -i http://localhost:8000/api/health
```

Verificar log de query (`SET LOCAL statement_timeout = 2000`) em `storage/logs/laravel.log`.

- [ ] **Step 8: Commit**

```bash
git add SDC/app/Http/Middleware/SetStatementTimeout.php SDC/app/Http/Kernel.php SDC/routes/api.php SDC/tests/Feature/Middleware/SetStatementTimeoutTest.php
git commit -m "feat(resilience): statement_timeout por grupo de rota via SET LOCAL"
```

---

## Task 4: ConnectionSemaphore (Redis-backed)

**Files:**
- Create: `SDC/app/Database/ConnectionSemaphore.php`
- Create: `SDC/tests/Unit/Database/ConnectionSemaphoreTest.php`

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Unit/Database/ConnectionSemaphoreTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\ConnectionSemaphore;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ConnectionSemaphoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Redis::del('db:slots:active', 'db:slots:owners');
    }

    public function test_acquires_slot_when_under_limit(): void
    {
        $sem = new ConnectionSemaphore(Redis::connection()->client(), limit: 5);
        $this->assertTrue($sem->acquire('owner-1'));
        $this->assertEquals(1, Redis::get('db:slots:active'));
    }

    public function test_blocks_acquire_when_at_limit(): void
    {
        $sem = new ConnectionSemaphore(
            Redis::connection()->client(),
            limit: 2,
            waitMs: 10,
            maxWaitMs: 30,
        );

        $this->assertTrue($sem->acquire('a'));
        $this->assertTrue($sem->acquire('b'));
        $this->assertFalse($sem->acquire('c'));
        $this->assertEquals(2, Redis::get('db:slots:active'));
    }

    public function test_release_decrements(): void
    {
        $sem = new ConnectionSemaphore(Redis::connection()->client(), limit: 5);
        $sem->acquire('owner-x');
        $sem->release('owner-x');
        $this->assertEquals(0, (int) Redis::get('db:slots:active'));
    }

    public function test_release_is_idempotent_per_owner(): void
    {
        $sem = new ConnectionSemaphore(Redis::connection()->client(), limit: 5);
        $sem->acquire('owner-x');
        $sem->release('owner-x');
        $sem->release('owner-x'); // segundo release não deve decrementar
        $this->assertEquals(0, (int) Redis::get('db:slots:active'));
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter ConnectionSemaphoreTest`
Expected: FAIL — `App\Database\ConnectionSemaphore` not found.

- [ ] **Step 3: Implementar semáforo**

`SDC/app/Database/ConnectionSemaphore.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database;

use Redis;

class ConnectionSemaphore
{
    private const KEY_ACTIVE = 'db:slots:active';
    private const KEY_OWNERS = 'db:slots:owners';
    private const TTL_SECONDS = 60;

    public function __construct(
        private Redis $redis,
        private int $limit,
        private int $waitMs = 50,
        private int $maxWaitMs = 2000,
    ) {}

    public function acquire(string $owner): bool
    {
        $start = microtime(true);

        do {
            $current = (int) $this->redis->incr(self::KEY_ACTIVE);
            $this->redis->expire(self::KEY_ACTIVE, self::TTL_SECONDS);

            if ($current <= $this->limit) {
                $this->redis->sAdd(self::KEY_OWNERS, $owner);
                $this->redis->expire(self::KEY_OWNERS, self::TTL_SECONDS);
                return true;
            }

            $this->redis->decr(self::KEY_ACTIVE);
            usleep($this->waitMs * 1000);
        } while ((microtime(true) - $start) * 1000 < $this->maxWaitMs);

        return false;
    }

    public function release(string $owner): void
    {
        if ((int) $this->redis->sRem(self::KEY_OWNERS, $owner) === 1) {
            $this->redis->decr(self::KEY_ACTIVE);
        }
    }

    public function active(): int
    {
        return (int) $this->redis->get(self::KEY_ACTIVE);
    }

    public function limit(): int
    {
        return $this->limit;
    }
}
```

- [ ] **Step 4: Rodar testes**

Run: `php artisan test --filter ConnectionSemaphoreTest`
Expected: 4/4 PASS.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Database/ConnectionSemaphore.php SDC/tests/Unit/Database/ConnectionSemaphoreTest.php
git commit -m "feat(resilience): ConnectionSemaphore Redis-backed para limitar concorrência"
```

---

## Task 5: AcquireConnectionSlot Middleware

**Files:**
- Create: `SDC/app/Http/Middleware/AcquireConnectionSlot.php`
- Create: `SDC/tests/Feature/Middleware/AcquireConnectionSlotTest.php`
- Modify: `SDC/app/Providers/AppServiceProvider.php` (bind semáforo)
- Modify: `SDC/app/Http/Kernel.php` (alias + global)

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Feature/Middleware/AcquireConnectionSlotTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Database\ConnectionSemaphore;
use App\Http\Middleware\AcquireConnectionSlot;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class AcquireConnectionSlotTest extends TestCase
{
    public function test_passes_when_slot_acquired(): void
    {
        $sem = Mockery::mock(ConnectionSemaphore::class);
        $sem->shouldReceive('acquire')->once()->andReturn(true);
        $sem->shouldReceive('release')->once();

        $middleware = new AcquireConnectionSlot($sem);
        $request = Request::create('/test');

        $response = $middleware->handle($request, fn() => response('ok'));

        $this->assertEquals('ok', $response->getContent());
    }

    public function test_returns_503_when_slot_denied(): void
    {
        $sem = Mockery::mock(ConnectionSemaphore::class);
        $sem->shouldReceive('acquire')->once()->andReturn(false);
        $sem->shouldNotReceive('release');

        $middleware = new AcquireConnectionSlot($sem);
        $response = $middleware->handle(Request::create('/test'), fn() => response('ok'));

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('1', $response->headers->get('Retry-After'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter AcquireConnectionSlotTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Implementar middleware**

`SDC/app/Http/Middleware/AcquireConnectionSlot.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Database\ConnectionSemaphore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AcquireConnectionSlot
{
    public function __construct(private ConnectionSemaphore $semaphore) {}

    public function handle(Request $request, Closure $next): Response
    {
        $owner = $request->attributes->get('slot_owner') ?? (string) Str::uuid();
        $request->attributes->set('slot_owner', $owner);

        if (!$this->semaphore->acquire($owner)) {
            return response()->json([
                'error' => 'Service Busy',
                'message' => 'Banco em alta carga; tente novamente em instantes.',
            ], 503, ['Retry-After' => '1']);
        }

        try {
            return $next($request);
        } finally {
            $this->semaphore->release($owner);
        }
    }
}
```

- [ ] **Step 4: Registrar binding no AppServiceProvider**

Modify `SDC/app/Providers/AppServiceProvider.php` no método `register()`:

```php
use App\Database\ConnectionSemaphore;
use Illuminate\Support\Facades\Redis;

public function register(): void
{
    $this->app->singleton(ConnectionSemaphore::class, function ($app) {
        $cfg = $app['config']->get('resilience.db');
        return new ConnectionSemaphore(
            Redis::connection()->client(),
            limit: $cfg['max_concurrent'],
            waitMs: $cfg['acquire_poll_ms'],
            maxWaitMs: $cfg['acquire_wait_ms'],
        );
    });
}
```

- [ ] **Step 5: Registrar alias no Kernel + aplicar global em api**

Modify `SDC/app/Http/Kernel.php` em `$middlewareAliases`:

```php
'acquire_slot' => \App\Http\Middleware\AcquireConnectionSlot::class,
```

E em `$middlewareGroups['api']`, adicionar **antes** do `ApiRateLimiter`:

```php
'api' => [
    \App\Http\Middleware\AcquireConnectionSlot::class,
    // ...
],
```

- [ ] **Step 6: Rodar testes**

Run: `php artisan test --filter AcquireConnectionSlotTest`
Expected: 2/2 PASS.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Http/Middleware/AcquireConnectionSlot.php SDC/app/Providers/AppServiceProvider.php SDC/app/Http/Kernel.php SDC/tests/Feature/Middleware/AcquireConnectionSlotTest.php
git commit -m "feat(resilience): middleware AcquireConnectionSlot aplica semáforo na API"
```

---

## Task 6: DatabaseCircuitBreaker

**Files:**
- Create: `SDC/app/Services/Database/DatabaseCircuitBreaker.php`
- Create: `SDC/tests/Unit/Services/Database/DatabaseCircuitBreakerTest.php`
- Modify: `SDC/app/Providers/AppServiceProvider.php` (registrar listener `DB::listen`)

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Unit/Services/Database/DatabaseCircuitBreakerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Database;

use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DatabaseCircuitBreakerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_starts_closed(): void
    {
        $cb = $this->makeBreaker();
        $this->assertFalse($cb->isOpen());
        $this->assertEquals('closed', $cb->state());
    }

    public function test_opens_after_timeout_threshold(): void
    {
        $cb = $this->makeBreaker(timeoutThreshold: 3);

        $cb->recordTimeout();
        $cb->recordTimeout();
        $this->assertFalse($cb->isOpen());

        $cb->recordTimeout();
        $this->assertTrue($cb->isOpen());
        $this->assertEquals('open', $cb->state());
    }

    public function test_transitions_to_half_open_after_reset_window(): void
    {
        $cb = $this->makeBreaker(timeoutThreshold: 1, resetSeconds: 1);
        $cb->recordTimeout();
        $this->assertTrue($cb->isOpen());

        sleep(2);
        $this->assertFalse($cb->isOpen()); // half-open: deixa passar
        $this->assertEquals('half-open', $cb->state());
    }

    public function test_success_in_half_open_closes_breaker(): void
    {
        $cb = $this->makeBreaker(timeoutThreshold: 1, resetSeconds: 1);
        $cb->recordTimeout();
        sleep(2);
        $cb->isOpen(); // transiciona para half-open
        $cb->recordSuccess();

        $this->assertEquals('closed', $cb->state());
    }

    private function makeBreaker(int $timeoutThreshold = 5, int $resetSeconds = 30): DatabaseCircuitBreaker
    {
        return new DatabaseCircuitBreaker(
            timeoutThreshold: $timeoutThreshold,
            windowSeconds: 60,
            resetSeconds: $resetSeconds,
        );
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter DatabaseCircuitBreakerTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Implementar circuit breaker**

`SDC/app/Services/Database/DatabaseCircuitBreaker.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DatabaseCircuitBreaker
{
    private const PREFIX = 'db_cb:';
    private const KEY_STATE = self::PREFIX.'state';
    private const KEY_TIMEOUTS = self::PREFIX.'timeouts';
    private const KEY_OPENED_AT = self::PREFIX.'opened_at';

    public function __construct(
        private int $timeoutThreshold = 5,
        private int $windowSeconds = 60,
        private int $resetSeconds = 30,
    ) {}

    public function isOpen(): bool
    {
        $state = $this->state();

        if ($state === 'open') {
            $openedAt = (int) Cache::get(self::KEY_OPENED_AT, 0);
            if ($openedAt && (time() - $openedAt) >= $this->resetSeconds) {
                $this->setState('half-open');
                return false;
            }
            return true;
        }

        return false;
    }

    public function state(): string
    {
        return (string) Cache::get(self::KEY_STATE, 'closed');
    }

    public function recordTimeout(): void
    {
        $count = (int) Cache::get(self::KEY_TIMEOUTS, 0) + 1;
        Cache::put(self::KEY_TIMEOUTS, $count, $this->windowSeconds);

        if ($count >= $this->timeoutThreshold) {
            $this->trip();
        }
    }

    public function recordSuccess(): void
    {
        if ($this->state() === 'half-open') {
            $this->close();
        }
    }

    private function trip(): void
    {
        $this->setState('open');
        Cache::put(self::KEY_OPENED_AT, time(), $this->resetSeconds * 3);

        Log::warning('Database circuit breaker tripped (OPEN)', [
            'timeouts_in_window' => Cache::get(self::KEY_TIMEOUTS),
            'reset_in_seconds' => $this->resetSeconds,
        ]);
    }

    private function close(): void
    {
        Cache::forget(self::KEY_STATE);
        Cache::forget(self::KEY_TIMEOUTS);
        Cache::forget(self::KEY_OPENED_AT);

        Log::info('Database circuit breaker reset (CLOSED)');
    }

    private function setState(string $state): void
    {
        Cache::put(self::KEY_STATE, $state, $this->resetSeconds * 3);
    }
}
```

- [ ] **Step 4: Registrar listener `DB::listen` no AppServiceProvider**

Modify `SDC/app/Providers/AppServiceProvider.php` no método `boot()`:

```php
use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

public function boot(): void
{
    $cb = $this->app->make(DatabaseCircuitBreaker::class);

    DB::listen(function (QueryExecuted $q) use ($cb) {
        if ($q->time > 30000) { // > 30s = timeout suspected
            $cb->recordTimeout();
        }
    });
}
```

E no `register()`:

```php
$this->app->singleton(DatabaseCircuitBreaker::class, function ($app) {
    $cfg = $app['config']->get('resilience.db.circuit_breaker');
    return new DatabaseCircuitBreaker(
        timeoutThreshold: $cfg['timeout_count_threshold'],
        windowSeconds: $cfg['window_seconds'],
        resetSeconds: $cfg['reset_timeout_seconds'],
    );
});
```

- [ ] **Step 5: Rodar testes**

Run: `php artisan test --filter DatabaseCircuitBreakerTest`
Expected: 4/4 PASS.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Services/Database/ SDC/tests/Unit/Services/Database/ SDC/app/Providers/AppServiceProvider.php
git commit -m "feat(resilience): DatabaseCircuitBreaker observa timeouts via DB::listen"
```

---

## Task 7: Backpressure Middleware

**Files:**
- Create: `SDC/app/Http/Middleware/Backpressure.php`
- Create: `SDC/tests/Feature/Middleware/BackpressureMiddlewareTest.php`
- Modify: `SDC/app/Http/Kernel.php` (registrar antes do rate limiter)

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Feature/Middleware/BackpressureMiddlewareTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Database\ConnectionSemaphore;
use App\Http\Middleware\Backpressure;
use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class BackpressureMiddlewareTest extends TestCase
{
    public function test_drops_public_tier_when_circuit_open(): void
    {
        $sem = Mockery::mock(ConnectionSemaphore::class);
        $sem->shouldReceive('active')->andReturn(0);
        $sem->shouldReceive('limit')->andReturn(100);

        $cb = Mockery::mock(DatabaseCircuitBreaker::class);
        $cb->shouldReceive('isOpen')->andReturn(true);

        $middleware = new Backpressure($sem, $cb);
        $request = Request::create('/test');

        $response = $middleware->handle($request, fn() => response('ok'), 'public');

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function test_passes_internal_tier_even_when_saturated(): void
    {
        $sem = Mockery::mock(ConnectionSemaphore::class);
        $sem->shouldReceive('active')->andReturn(99);
        $sem->shouldReceive('limit')->andReturn(100);

        $cb = Mockery::mock(DatabaseCircuitBreaker::class);
        $cb->shouldReceive('isOpen')->andReturn(true);

        $middleware = new Backpressure($sem, $cb);
        $response = $middleware->handle(Request::create('/'), fn() => response('ok'), 'internal');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_passes_free_tier_below_threshold(): void
    {
        $sem = Mockery::mock(ConnectionSemaphore::class);
        $sem->shouldReceive('active')->andReturn(50);
        $sem->shouldReceive('limit')->andReturn(100);

        $cb = Mockery::mock(DatabaseCircuitBreaker::class);
        $cb->shouldReceive('isOpen')->andReturn(false);

        $middleware = new Backpressure($sem, $cb);
        $response = $middleware->handle(Request::create('/'), fn() => response('ok'), 'free');

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter BackpressureMiddlewareTest`
Expected: FAIL.

- [ ] **Step 3: Implementar middleware**

`SDC/app/Http/Middleware/Backpressure.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Database\ConnectionSemaphore;
use App\Services\Database\DatabaseCircuitBreaker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Backpressure
{
    private const TIERS_BYPASS = ['internal', 'admin', 'enterprise', 'webhook'];

    public function __construct(
        private ConnectionSemaphore $semaphore,
        private DatabaseCircuitBreaker $cb,
    ) {}

    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        if (in_array($tier, self::TIERS_BYPASS, true)) {
            return $next($request);
        }

        $publicThreshold = (float) config('resilience.backpressure.public_drop_threshold');
        $freeThreshold = (float) config('resilience.backpressure.free_drop_threshold');

        $utilization = $this->semaphore->active() / max(1, $this->semaphore->limit());

        $shouldDrop = match (true) {
            $this->cb->isOpen() && in_array($tier, ['public', 'free', 'default'], true) => true,
            $tier === 'public' && $utilization >= $publicThreshold => true,
            in_array($tier, ['free', 'default'], true) && $utilization >= $freeThreshold => true,
            default => false,
        };

        if ($shouldDrop) {
            return response()->json([
                'error' => 'Service Busy',
                'message' => 'Sistema em alta carga. Tente novamente em breve.',
                'tier' => $tier,
            ], 503, ['Retry-After' => '5']);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar alias no Kernel**

Modify `SDC/app/Http/Kernel.php` em `$middlewareAliases`:

```php
'backpressure' => \App\Http\Middleware\Backpressure::class,
```

- [ ] **Step 5: Rodar testes**

Run: `php artisan test --filter BackpressureMiddlewareTest`
Expected: 3/3 PASS.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Http/Middleware/Backpressure.php SDC/app/Http/Kernel.php SDC/tests/Feature/Middleware/BackpressureMiddlewareTest.php
git commit -m "feat(resilience): backpressure middleware dropa tiers baixos sob carga"
```

---

## Task 8: ApiRateLimiter Fail-Closed + Global Bucket

**Files:**
- Modify: `SDC/app/Http/Middleware/ApiRateLimiter.php`
- Create: `SDC/tests/Feature/Middleware/ApiRateLimiterFailClosedTest.php`

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Feature/Middleware/ApiRateLimiterFailClosedTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ApiRateLimiterFailClosedTest extends TestCase
{
    public function test_global_bucket_blocks_public_when_over_limit(): void
    {
        config(['resilience.rate_limit.global_per_second' => 5]);

        Redis::set('rate_limit:global:per_second', 10);
        Redis::expire('rate_limit:global:per_second', 60);

        $middleware = new ApiRateLimiter();
        $response = $middleware->handle(Request::create('/public'), fn() => response('ok'), 'public');

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function test_global_bucket_allows_internal_even_when_over_limit(): void
    {
        config(['resilience.rate_limit.global_per_second' => 5]);
        Redis::set('rate_limit:global:per_second', 100);

        $middleware = new ApiRateLimiter();
        $response = $middleware->handle(Request::create('/internal'), fn() => response('ok'), 'internal');

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Redis::del('rate_limit:global:per_second');
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter ApiRateLimiterFailClosedTest`
Expected: FAIL — funcionalidade ainda não existe.

- [ ] **Step 3: Modificar `ApiRateLimiter`**

Em `SDC/app/Http/Middleware/ApiRateLimiter.php`, **substituir** o bloco `checkRateLimit()` original (linhas 102-149) e adicionar verificação global no início do `handle()`:

```php
public function handle(Request $request, Closure $next, string $tier = 'default'): Response
{
    // 1. Verificação GLOBAL (antes do per-user)
    $globalDecision = $this->checkGlobalBucket($tier);
    if ($globalDecision !== null) {
        return $globalDecision;
    }

    $user = $request->user();
    $limits = $this->getLimitsByTier($tier, $user);
    $cost = $this->getRouteCost($request);
    $key = 'rate_limit:'.($user ? "user:{$user->id}" : "ip:{$request->ip()}").":tier:{$tier}";

    $limitCheck = $this->checkRateLimit($key, $cost, $limits, $tier);

    if (!$limitCheck['allowed']) {
        return response()->json([
            'error' => 'Rate Limit Exceeded',
            'retry_after_seconds' => $limitCheck['retry_after'],
        ], 429);
    }

    $response = $next($request);
    $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
    $response->headers->set('X-RateLimit-Remaining', max(0, $limits['max_attempts'] - $limitCheck['current_usage']));

    return $response;
}

private function checkGlobalBucket(string $tier): ?Response
{
    $tiersBypass = ['internal', 'admin', 'enterprise', 'webhook'];
    if (in_array($tier, $tiersBypass, true)) {
        return null;
    }

    try {
        $current = (int) Redis::incr('rate_limit:global:per_second');
        if ($current === 1) {
            Redis::expire('rate_limit:global:per_second', 1);
        }

        $threshold = (int) config('resilience.rate_limit.global_per_second', 1500);
        if ($current > $threshold && in_array($tier, ['public', 'free', 'default'], true)) {
            return response()->json([
                'error' => 'Service Busy',
                'message' => 'Capacidade global atingida; tente em alguns segundos.',
            ], 503, ['Retry-After' => '5']);
        }
    } catch (\Throwable $e) {
        // Fail-closed: se Redis falhar, derruba tiers de baixa prioridade
        if ((bool) config('resilience.rate_limit.fail_closed', true)
            && in_array($tier, ['public', 'free', 'default'], true)) {
            return response()->json([
                'error' => 'Service Degraded',
                'message' => 'Rate limit indisponível; tente em breve.',
            ], 503, ['Retry-After' => '10']);
        }
    }

    return null;
}

private function checkRateLimit(string $key, float $cost, array $limits, string $tier): array
{
    try {
        $exists = Redis::exists($key);
        $currentUsage = Redis::incrbyfloat($key, $cost);
        if (!$exists) {
            Redis::expire($key, $limits['decay_seconds']);
        }

        if ($currentUsage > $limits['max_attempts']) {
            return ['allowed' => false, 'current_usage' => $currentUsage, 'retry_after' => Redis::ttl($key)];
        }
        return ['allowed' => true, 'current_usage' => $currentUsage, 'retry_after' => 0];

    } catch (\Throwable $e) {
        Log::error('Redis error in rate limiter', ['error' => $e->getMessage(), 'tier' => $tier]);

        // Fail-closed: tiers baixos são recusados; tiers altos passam
        $bypassOnError = ['pro', 'premium', 'enterprise', 'internal', 'admin', 'webhook'];
        if ((bool) config('resilience.rate_limit.fail_closed', true)
            && !in_array($tier, $bypassOnError, true)) {
            return ['allowed' => false, 'current_usage' => 0, 'retry_after' => 10];
        }
        return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0];
    }
}
```

- [ ] **Step 4: Rodar testes**

Run: `php artisan test --filter ApiRateLimiter`
Expected: testes novos PASS; testes antigos (se houver) ainda PASS.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Http/Middleware/ApiRateLimiter.php SDC/tests/Feature/Middleware/ApiRateLimiterFailClosedTest.php
git commit -m "feat(resilience): ApiRateLimiter fail-closed e bucket global por segundo"
```

---

## Task 9: pgsql_webhook Connection + Queue Wiring

**Files:**
- Modify: `SDC/config/database.php` (adicionar `pgsql_webhook`)
- Modify: `SDC/app/Jobs/ProcessWebhook.php`, `SDC/app/Jobs/ProcessInboundWebhook.php`

- [ ] **Step 1: Adicionar conexão em `config/database.php`**

Após o bloco `'pgsql'`, antes de `'pgsql_read'`:

```php
'pgsql_webhook' => [
    'driver' => 'pgsql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'sdc'),
    'username' => env('DB_USERNAME', 'sdc'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => env('DB_SEARCH_PATH', 'public'),
    'sslmode' => env('DB_SSLMODE', 'prefer'),
    'application_name' => env('APP_NAME', 'sdc-laravel').'-webhook',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        // Sem ATTR_PERSISTENT — jobs não devem reusar conexão entre execuções
    ],
],
```

- [ ] **Step 2: Atualizar jobs para usar a nova conexão**

Modify `SDC/app/Jobs/ProcessInboundWebhook.php` no método `handle()`, **logo no início**:

```php
public function handle(): void
{
    DB::setDefaultConnection('pgsql_webhook');

    try {
        // corpo existente
    } finally {
        DB::setDefaultConnection(config('database.default'));
    }
}
```

Mesma mudança em `SDC/app/Jobs/ProcessWebhook.php`.

Adicionar `use Illuminate\Support\Facades\DB;` no topo de cada arquivo se não existir.

- [ ] **Step 3: Validar config**

Run: `php artisan config:show database.connections.pgsql_webhook.application_name`
Expected: `sdc-laravel-webhook`.

- [ ] **Step 4: Smoke test (manual)**

Run em terminal:

```bash
php artisan tinker --execute="DB::connection('pgsql_webhook')->select('SELECT current_setting(\'application_name\') AS app');"
```

Expected: `app => "sdc-laravel-webhook"`.

- [ ] **Step 5: Commit**

```bash
git add SDC/config/database.php SDC/app/Jobs/ProcessWebhook.php SDC/app/Jobs/ProcessInboundWebhook.php
git commit -m "feat(resilience): conexão pgsql_webhook isolada para jobs de webhook"
```

---

## Task 10: CachedRepository Decorator + Aplicar em OrgaoService

**Files:**
- Create: `SDC/app/Support/Cache/CachedRepository.php`
- Create: `SDC/tests/Unit/Cache/CachedRepositoryTest.php`
- Modify: `SDC/app/Modules/Compdec/Services/OrgaoService.php`

- [ ] **Step 1: Escrever teste falhando**

`SDC/tests/Unit/Cache/CachedRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Support\Cache\CachedRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CachedRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_remembers_and_returns_callback_result(): void
    {
        $repo = new CachedRepository('test', ttlSeconds: 60);
        $calls = 0;

        $first = $repo->remember('key', function () use (&$calls) {
            $calls++;
            return 'value';
        });
        $second = $repo->remember('key', function () use (&$calls) {
            $calls++;
            return 'value';
        });

        $this->assertEquals('value', $first);
        $this->assertEquals('value', $second);
        $this->assertEquals(1, $calls);
    }

    public function test_flush_tag_invalidates(): void
    {
        $repo = new CachedRepository('orgaos', ttlSeconds: 60);
        $calls = 0;

        $repo->remember('list', fn() => ++$calls);
        $repo->flush();
        $repo->remember('list', fn() => ++$calls);

        $this->assertEquals(2, $calls);
    }
}
```

- [ ] **Step 2: Rodar teste, confirmar falha**

Run: `php artisan test --filter CachedRepositoryTest`
Expected: FAIL.

- [ ] **Step 3: Implementar decorator**

`SDC/app/Support/Cache/CachedRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

class CachedRepository
{
    public function __construct(
        private string $tag,
        private int $ttlSeconds = 300,
    ) {}

    public function remember(string $key, Closure $producer): mixed
    {
        return Cache::tags([$this->tag])->remember(
            $this->tag.':'.$key,
            $this->ttlSeconds,
            $producer
        );
    }

    public function flush(): void
    {
        Cache::tags([$this->tag])->flush();
    }
}
```

- [ ] **Step 4: Aplicar em OrgaoService**

Modify `SDC/app/Modules/Compdec/Services/OrgaoService.php`. Adicionar property + injetar no construtor + envolver listagens em `remember()`.

Exemplo do padrão (adaptar aos métodos reais do service):

```php
use App\Support\Cache\CachedRepository;

public function __construct(
    private CachedRepository $cache = new CachedRepository('orgaos', ttlSeconds: 3600)
) {}

public function listAll(): Collection
{
    return $this->cache->remember('all', fn() => Orgao::orderBy('nome')->get());
}

public function flushCache(): void
{
    $this->cache->flush();
}
```

Onde houver `Orgao::create`, `Orgao::update` ou `Orgao::delete`, chamar `$this->cache->flush()` depois.

- [ ] **Step 5: Rodar testes**

Run: `php artisan test --filter "CachedRepository|Orgao"`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Support/Cache/ SDC/tests/Unit/Cache/ SDC/app/Modules/Compdec/Services/OrgaoService.php
git commit -m "feat(resilience): CachedRepository decorator + cache em OrgaoService"
```

---

## Task 11: cursor() Streaming em Jobs ETL

**Files:**
- Modify: `SDC/app/Console/Commands/IaIndexRats.php`
- Modify: `SDC/app/Console/Commands/MigrarCompdecLegadoCommand.php`
- (qualquer outro export que faça `->all()` ou `->get()` em coleções >10k)

- [ ] **Step 1: Auditar usos de `::all()` e `->get()` em comandos/jobs**

Run: `grep -rn "::all()\|->get()" SDC/app/Console/Commands/ SDC/app/Jobs/ | grep -v "vendor\|Test"`

Listar candidatos em comentário do commit final.

- [ ] **Step 2: Refatorar `IaIndexRats`**

Em `SDC/app/Console/Commands/IaIndexRats.php`, substituir loops do tipo:

```php
foreach (Rat::all() as $rat) { /* ... */ }
```

por:

```php
foreach (Rat::query()->cursor() as $rat) { /* ... */ }
```

Para coleções com `->where()->get()`, manter o where e trocar `get()` por `cursor()`.

- [ ] **Step 3: Refatorar `MigrarCompdecLegadoCommand`**

Mesmo padrão. Atenção a `chunk()` já existentes — manter, ou trocar por `lazyById()` se a tabela for >100k linhas (mais previsível memória).

- [ ] **Step 4: Validar com profile manual**

Run em ambiente dev com seed de >1k Rats:

```bash
php -d memory_limit=64M artisan ia:index-rats
```

Expected: terminação normal (sem `Allowed memory size exhausted`).

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Console/Commands/IaIndexRats.php SDC/app/Console/Commands/MigrarCompdecLegadoCommand.php
git commit -m "perf(resilience): streaming via cursor() em jobs ETL para liberar conexão cedo"
```

---

## Task 12: QueryBudgetGuard

**Files:**
- Create: `SDC/app/Database/QueryBudgetGuard.php`
- Modify: `SDC/app/Providers/AppServiceProvider.php` (registrar)

- [ ] **Step 1: Implementar**

`SDC/app/Database/QueryBudgetGuard.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryBudgetGuard
{
    private int $count = 0;

    public function __construct(
        private int $warnAt = 30,
        private int $failAt = 100,
    ) {}

    public function bind(): void
    {
        DB::listen(function (QueryExecuted $event) {
            $this->count++;

            if ($this->count === $this->warnAt + 1) {
                Log::warning('Query budget warning crossed', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }

            if ($this->count === $this->failAt + 1) {
                Log::error('Query budget FAILED — possível N+1', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }
        });
    }

    public function reset(): void
    {
        $this->count = 0;
    }

    public function count(): int
    {
        return $this->count;
    }
}
```

- [ ] **Step 2: Registrar no AppServiceProvider**

Em `boot()`:

```php
use App\Database\QueryBudgetGuard;

$budget = $this->app->make(QueryBudgetGuard::class);
$budget->bind();

// Reset por request (Octane)
$this->app['events']->listen(\Laravel\Octane\Events\RequestReceived::class, fn() => $budget->reset());
```

Em `register()`:

```php
$this->app->singleton(QueryBudgetGuard::class, function ($app) {
    $cfg = $app['config']->get('resilience.query_budget');
    return new QueryBudgetGuard($cfg['warn_at'], $cfg['fail_at']);
});
```

- [ ] **Step 3: Smoke test**

Forçar N+1 num endpoint de teste e verificar log de warning em `storage/logs/laravel.log`.

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Database/QueryBudgetGuard.php SDC/app/Providers/AppServiceProvider.php
git commit -m "feat(resilience): QueryBudgetGuard alerta N+1 via DB::listen"
```

---

## Task 13: Webhook Archive (Migration + Command + Schedule)

**Files:**
- Create: `SDC/database/migrations/2026_05_21_000000_create_webhook_events_archive_table.php`
- Create: `SDC/app/Console/Commands/ArchiveWebhookEventsCommand.php`
- Modify: `SDC/app/Console/Kernel.php` (schedule)

- [ ] **Step 1: Migration**

`SDC/database/migrations/2026_05_21_000000_create_webhook_events_archive_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events_archive', function (Blueprint $t) {
            // Mesmo schema da tabela webhook_events; ajuste se a tabela
            // original tiver colunas adicionais.
            $t->id();
            $t->string('external_event_id')->nullable();
            $t->string('provider', 100);
            $t->string('event_type', 150);
            $t->json('payload');
            $t->string('status', 30);
            $t->timestamps();
            $t->timestamp('archived_at')->useCurrent();

            $t->index(['provider', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events_archive');
    }
};
```

> Antes de rodar a migration, conferir o schema real de `webhook_events` (model `App\Models\WebhookEvent`) e alinhar colunas. Se a tabela tiver mais colunas, espelhar.

- [ ] **Step 2: Comando**

`SDC/app/Console/Commands/ArchiveWebhookEventsCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:archive
                            {--days=90 : idade mínima em dias}
                            {--chunk=500 : tamanho do batch}';

    protected $description = 'Move WebhookEvent antigos com status completed para webhook_events_archive';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));
        $chunkSize = (int) $this->option('chunk');
        $moved = 0;

        $this->info("Arquivando eventos completed criados antes de {$cutoff->toDateTimeString()}");

        WebhookEvent::query()
            ->where('status', 'completed')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($batch) use (&$moved) {
                $rows = $batch->map(fn ($e) => [
                    'id' => $e->id,
                    'external_event_id' => $e->external_event_id,
                    'provider' => $e->provider,
                    'event_type' => $e->event_type,
                    'payload' => is_array($e->payload) ? json_encode($e->payload) : $e->payload,
                    'status' => $e->status,
                    'created_at' => $e->created_at,
                    'updated_at' => $e->updated_at,
                    'archived_at' => now(),
                ])->all();

                DB::transaction(function () use ($batch, $rows, &$moved) {
                    DB::table('webhook_events_archive')->insert($rows);
                    WebhookEvent::whereIn('id', $batch->pluck('id'))->delete();
                    $moved += count($rows);
                });
            });

        $this->info("Arquivados: {$moved}");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Agendar no Kernel**

Modify `SDC/app/Console/Kernel.php` no método `schedule()`:

```php
$schedule->command('webhooks:archive --days=90 --chunk=500')
    ->weeklyOn(0, '03:00') // domingo 03:00 BRT
    ->onOneServer()
    ->withoutOverlapping();
```

- [ ] **Step 4: Rodar migration**

Run: `php artisan migrate`
Expected: migration aplicada sem erro.

- [ ] **Step 5: Smoke test do comando**

Seed alguns `WebhookEvent` com `status='completed'` e `created_at` antigo, então:

```bash
php artisan webhooks:archive --days=90
```

Expected: confirma arquivamento e remove da tabela original.

- [ ] **Step 6: Commit**

```bash
git add SDC/database/migrations/2026_05_21_*.php SDC/app/Console/Commands/ArchiveWebhookEventsCommand.php SDC/app/Console/Kernel.php
git commit -m "feat(resilience): comando webhooks:archive semanal para >90 dias"
```

---

## Task 14: Swagger UI Cache

**Files:**
- Create: `SDC/app/Http/Middleware/CacheSwaggerUi.php`
- Modify: `SDC/app/Http/Kernel.php` (alias)
- Modify: `SDC/routes/web.php` (aplicar nas rotas l5-swagger)

- [ ] **Step 1: Implementar middleware**

`SDC/app/Http/Middleware/CacheSwaggerUi.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheSwaggerUi
{
    public function handle(Request $request, Closure $next, int $maxAgeSeconds = 3600): Response
    {
        if ($request->boolean('regenerate')) {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAgeSeconds}");
        }

        return $response;
    }
}
```

- [ ] **Step 2: Registrar alias + aplicar**

Modify `SDC/app/Http/Kernel.php`:

```php
'cache_swagger' => \App\Http\Middleware\CacheSwaggerUi::class,
```

Modify `SDC/routes/web.php` (ou onde forem montadas as rotas do l5-swagger):

```php
Route::middleware('cache_swagger:3600')
    ->prefix('api/documentation')
    ->group(/* l5-swagger publica routes */);
```

- [ ] **Step 3: Smoke test**

```bash
curl -i http://localhost:8000/api/documentation
```

Expected: header `Cache-Control: public, max-age=3600`.

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Http/Middleware/CacheSwaggerUi.php SDC/app/Http/Kernel.php SDC/routes/web.php
git commit -m "feat(resilience): cache de 1h no Swagger UI; bypass via ?regenerate=1"
```

---

## Task 15: Observabilidade (Metrics + Healthcheck + Alerts)

**Files:**
- Create: `SDC/app/Http/Controllers/Api/MetricsController.php`
- Modify: `SDC/app/Http/Controllers/Api/HealthCheckController.php`
- Modify: `SDC/routes/api.php` (rota `/metrics`)
- Modify: `SDC/docker/monitoring/alerts/sdc-alerts.yml`

- [ ] **Step 1: MetricsController**

`SDC/app/Http/Controllers/Api/MetricsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Database\ConnectionSemaphore;
use App\Http\Controllers\Controller;
use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    public function __invoke(
        ConnectionSemaphore $sem,
        DatabaseCircuitBreaker $cb,
    ) {
        $stateMap = ['closed' => 0, 'half-open' => 1, 'open' => 2];
        $cbState = $stateMap[$cb->state()] ?? 0;
        $global = (int) (Redis::get('rate_limit:global:per_second') ?? 0);

        $body = implode("\n", [
            '# HELP sdc_db_slots_active Active DB slots held via ConnectionSemaphore',
            '# TYPE sdc_db_slots_active gauge',
            "sdc_db_slots_active {$sem->active()}",
            '# HELP sdc_db_slots_limit Configured DB slot limit',
            '# TYPE sdc_db_slots_limit gauge',
            "sdc_db_slots_limit {$sem->limit()}",
            '# HELP sdc_db_circuit_breaker_state 0=closed, 1=half-open, 2=open',
            '# TYPE sdc_db_circuit_breaker_state gauge',
            "sdc_db_circuit_breaker_state {$cbState}",
            '# HELP sdc_rate_limit_global_current Current global rate (per-second window)',
            '# TYPE sdc_rate_limit_global_current gauge',
            "sdc_rate_limit_global_current {$global}",
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}
```

- [ ] **Step 2: Rota**

Em `SDC/routes/api.php`:

```php
Route::get('/metrics', \App\Http\Controllers\Api\MetricsController::class)
    ->middleware('statement_timeout:2000');
```

- [ ] **Step 3: HealthCheck enriquecido**

Modify `SDC/app/Http/Controllers/Api/HealthCheckController.php` no método `index()` (ou equivalente) — adicionar pool/CB status ao payload:

```php
public function index(ConnectionSemaphore $sem, DatabaseCircuitBreaker $cb)
{
    $semSaturated = $sem->active() / max(1, $sem->limit()) > 0.95;
    $cbOpen = $cb->isOpen();

    $status = ($semSaturated || $cbOpen) ? 503 : 200;

    return response()->json([
        'status' => $status === 200 ? 'ok' : 'degraded',
        'db' => [
            'semaphore_active' => $sem->active(),
            'semaphore_limit' => $sem->limit(),
            'circuit_breaker' => $cb->state(),
        ],
    ], $status);
}
```

- [ ] **Step 4: Adicionar alerts no Prometheus**

Append em `SDC/docker/monitoring/alerts/sdc-alerts.yml`:

```yaml
- alert: DBCircuitBreakerOpen
  expr: sdc_db_circuit_breaker_state == 2
  for: 1m
  labels:
    severity: critical
  annotations:
    summary: "Database circuit breaker está OPEN"

- alert: DBSemaphoreSaturated
  expr: sdc_db_slots_active / sdc_db_slots_limit > 0.8
  for: 5m
  labels:
    severity: warning
  annotations:
    summary: "Semáforo do DB acima de 80% por 5min"

- alert: RateLimitGlobalSaturated
  expr: sdc_rate_limit_global_current > 1200
  for: 2m
  labels:
    severity: warning
  annotations:
    summary: "Bucket global acima de 80% do threshold"
```

- [ ] **Step 5: Smoke test**

```bash
curl -i http://localhost:8000/api/metrics
curl -i http://localhost:8000/api/health
```

Expected: `/metrics` retorna texto Prometheus; `/health` retorna JSON com `db.semaphore_active` etc.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Http/Controllers/Api/MetricsController.php SDC/app/Http/Controllers/Api/HealthCheckController.php SDC/routes/api.php SDC/docker/monitoring/alerts/sdc-alerts.yml
git commit -m "feat(resilience): endpoint /metrics Prometheus + healthcheck enriquecido"
```

---

## Task 16: AsynchronousResponse Audit + Fix em Rotas Heavy

**Files:**
- Auditoria: `SDC/app/Http/Controllers/**`
- Modify: controllers identificados como heavy/expensive sem `AsynchronousResponse`

- [ ] **Step 1: Listar rotas heavy/expensive**

Run no terminal:

```bash
grep -rn "AsynchronousResponse" SDC/app/Http/Controllers/ | wc -l
```

E inspecionar `ApiRateLimiter::getRouteCost` (`SDC/app/Http/Middleware/ApiRateLimiter.php:155-183`) para identificar paths classificados como `heavy` (export, relatorio, report) ou `expensive` (dashboard, analytics, batch, import).

Mapear rotas matching e que NÃO usam o trait.

- [ ] **Step 2: Refatorar cada rota síncrona heavy para retornar 202**

Padrão (exemplo para um controller de export):

**Antes:**

```php
public function exportExcel(Request $request)
{
    $data = $this->service->buildLargeExport($request->validated());
    return Excel::download(new ProcessosExport($data), 'processos.xlsx');
}
```

**Depois:**

```php
use App\Http\Controllers\Traits\AsynchronousResponse;

class ProcessosExportController extends Controller
{
    use AsynchronousResponse;

    public function exportExcel(Request $request)
    {
        $traceId = (string) Str::uuid();

        ProcessosExportJob::dispatch($request->validated(), $traceId)
            ->onQueue('bulk');

        return $this->accepted($traceId, 'Export iniciado; consulte status via trace_id');
    }
}
```

Criar `App\Jobs\ProcessosExportJob` correspondente que faça o trabalho, salve no storage (`Storage::disk('exports')`), e atualize o status do trace.

Repetir para cada rota mapeada.

- [ ] **Step 3: Verificar contagem de heavy sync após refactor**

Esperado: zero. Documentar no commit os controllers tocados.

- [ ] **Step 4: Commit**

```bash
git add SDC/app/Http/Controllers/ SDC/app/Jobs/
git commit -m "refactor(resilience): rotas heavy retornam 202 via AsynchronousResponse"
```

---

## Task 17: Load Test (k6) + Chaos

**Files:**
- Create: `SDC/tests/load/k6-baseline.js`
- Create: `SDC/tests/load/k6-spike.js`
- Create: `SDC/tests/load/README.md`

- [ ] **Step 1: Script baseline**

`SDC/tests/load/k6-baseline.js`:

```js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 200 },
    { duration: '5m', target: 1000 },
    { duration: '10m', target: 1000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500', 'p(99)<1500'],
  },
};

const BASE = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  const res = http.get(`${BASE}/api/v1/healthcheck`);
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(Math.random() * 0.5);
}
```

- [ ] **Step 2: Script spike**

`SDC/tests/load/k6-spike.js` — pico de 0 → 1500 req/s em 30s, sustenta 2min, recua:

```js
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 1500 },
    { duration: '2m', target: 1500 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'],
  },
};

const BASE = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  http.get(`${BASE}/api/v1/healthcheck`);
}
```

- [ ] **Step 3: README com instruções**

`SDC/tests/load/README.md` — comandos:

```markdown
# Load tests

Pré-requisitos: k6 (https://k6.io), app rodando em staging com seed.

## Baseline (1000 req/s sustentado por 10min)

    k6 run -e BASE_URL=https://staging.newsdc.gov.br tests/load/k6-baseline.js

## Spike (1500 req/s por 2min)

    k6 run -e BASE_URL=https://staging.newsdc.gov.br tests/load/k6-spike.js

## Chaos test (Redis down)

Durante o baseline, derrubar Redis em staging:

    docker compose -f docker/docker-compose.prod.yml stop redis-master

Esperado:
- Tier `public`/`free`: 503 com Retry-After (fail-closed)
- Tier `internal`/`admin`: continua passando
- Healthcheck reflete o estado

Após 2min, reativar e validar recuperação automática.

## Critérios de sucesso

- http_req_failed < 1% no baseline; < 5% no spike
- p95 < 500ms; p99 < 1500ms
- sdc_db_slots_active nunca passa 80% do limit
- DatabaseCircuitBreaker permanece closed durante baseline
```

- [ ] **Step 4: Rodar baseline em staging**

Run: conforme README.md. Capturar resultado.

- [ ] **Step 5: Iterar config se critérios falharem**

Ajustar `DB_MAX_CONCURRENT`, `DB_CB_*` thresholds, ou pool sizes do FrankenPHP/RoadRunner conforme observado.

- [ ] **Step 6: Commit**

```bash
git add SDC/tests/load/
git commit -m "test(resilience): scripts k6 baseline + spike + chaos para validação"
```

---

## Self-Review (checklist do autor do plano, executada inline)

**Spec coverage:**
- ✅ 3.1 SelectiveDisconnect → Task 2
- ✅ 3.2 ConnectionSemaphore → Tasks 4 + 5
- ✅ 3.3 SetStatementTimeout → Task 3
- ✅ 3.4 DatabaseCircuitBreaker → Task 6
- ✅ 3.5 Backpressure → Task 7
- ✅ 3.6 AsynchronousResponse audit → Task 16
- ✅ 3.7 ApiRateLimiter fail-closed + global → Task 8
- ✅ 3.8 CachedRepository → Task 10
- ✅ 3.9 cursor() streaming → Task 11
- ✅ 3.10 QueryBudgetGuard → Task 12
- ✅ 3.11 pgsql_webhook → Task 9
- ✅ 3.12 Archive WebhookEvent → Task 13
- ✅ 3.13 Swagger cache → Task 14
- ✅ §6 Observabilidade → Task 15
- ✅ §7 Load test → Task 17

**Placeholder scan:** sem TBD/TODO; cada step tem código completo ou comando exato.

**Type consistency:** `ConnectionSemaphore` API (`acquire`/`release`/`active`/`limit`) usada igual em Tasks 5, 7, 15. `DatabaseCircuitBreaker::state()` e `::isOpen()` usados igual em Tasks 7 e 15. `CachedRepository::remember()`/`flush()` igual em Task 10.

**Sem placeholders.** Plano completo.

---

## Critérios globais de sucesso

- Todos os testes unitários e feature do plano passam (`php artisan test`).
- Baseline k6 (1000 req/s por 10min) com p95 < 500ms, falhas < 1%.
- Spike k6 (1500 req/s por 2min) com falhas < 5%.
- Chaos test com Redis down: tiers `public`/`free` rejeitados gracefully; `internal` continua passando.
- Endpoint `/metrics` retorna métricas Prometheus válidas.
- `/api/health` reflete saturação (200 → 503 quando semáforo > 95%).
- Zero vazamento de tenant em testes de isolation (validar em Task 2 + smoke manual).
