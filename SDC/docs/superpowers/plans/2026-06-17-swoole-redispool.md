# SwooleRedisPool (Fase 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o Redis coroutine-safe sob Swoole (pool por-coroutine + bind transparente no RedisManager) para reativar `SWOOLE_HOOK_ALL` sem a colisão "Socket already bound to another coroutine".

**Architecture:** Pool de conexões phpredis-TLS por worker (espelha `SwoolePdoPool`, via `Swoole\Coroutine\Channel`). Um `CoroutineRedisManager` (estende `Illuminate\Redis\RedisManager`) empresta uma conexão própria por `Coroutine::getCid()` no 1º uso e devolve no `RequestTerminated`. Fora de coroutine / hooks off → comportamento padrão do Laravel.

**Tech Stack:** Laravel 12 (Octane/Swoole), phpredis, Swoole 6 (`Coroutine`, `Channel`), Azure Redis (TLS 6380).

## Global Constraints

- Sem emojis no código (apenas em mensagens de commit, gitmoji). [verbatim regra 2]
- DRY/SOLID. [regra 4]
- **Testes NÃO entram no commit** — TDD local, mas `git add` só de produção. [regra 10]
- phpredis nativo (não predis) — `ConnectionSemaphore` exige `?Redis`.
- Conexões: `default` (db 0) e `cache` (db 1), TLS `tls://host:6380`.
- O pool/bind só agem sob Swoole; fora disso, caminho padrão intacto (FrankenPHP/CLI/testes).
- `OCTANE_HOOK_FLAGS_ENABLED` default `false` — só ligar após load-test verde.

---

## File Structure

- Create `app/Support/Redis/SwooleRedisPool.php` — pool de conexões phpredis-TLS por conexão lógica (uma instância por `default`/`cache`).
- Create `app/Support/Redis/CoroutineRedisManager.php` — estende `RedisManager`, empresta/devolve por cid.
- Modify `app/Providers/OctaneServiceProvider.php` — boot dos pools (`WorkerStarting`), bind do manager (`register`), devolução (`RequestTerminated`).
- Test (NÃO commitar) `tests/Unit/Support/Redis/SwooleRedisPoolTest.php`, `tests/Unit/Support/Redis/CoroutineRedisManagerTest.php`.
- Doc/validação: load-test descrito na Task 4.

---

### Task 1: SwooleRedisPool

**Files:**
- Create: `app/Support/Redis/SwooleRedisPool.php`
- Test (local, não commitar): `tests/Unit/Support/Redis/SwooleRedisPoolTest.php`

**Interfaces:**
- Produces:
  - `SwooleRedisPool::fromConnection(string $connection, int $size = 16, float $timeout = 3.0): self`
  - `run(callable $fn): mixed` — empresta `\Redis`, executa `$fn(\Redis $r)`, devolve/descarta, retorna o resultado.
  - `acquire(): \Redis` / `release(\Redis $r): void` / `discard(): void`

- [ ] **Step 1: Write the failing test** (`tests/Unit/Support/Redis/SwooleRedisPoolTest.php`)

```php
<?php

namespace Tests\Unit\Support\Redis;

use App\Support\Redis\SwooleRedisPool;
use PHPUnit\Framework\TestCase;

final class SwooleRedisPoolTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('requer ext-swoole (Channel)');
        }
    }

    public function test_run_em_coroutine_empresta_e_devolve(): void
    {
        // Pool com fabrica injetada (sem Redis real): conta create/borrow/return.
        \Swoole\Coroutine\run(function () {
            $created = 0;
            $pool = SwooleRedisPool::forTesting(
                size: 2,
                timeout: 1.0,
                factory: function () use (&$created) { $created++; return new \stdClass(); }
            );

            $r = $pool->run(fn ($conn) => 'ok');

            $this->assertSame('ok', $r);
            $this->assertSame(1, $created);          // criou 1 sob demanda
            $this->assertSame(1, $pool->available()); // devolveu ao Channel
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=SwooleRedisPoolTest`
Expected: FAIL — `Error: Class "App\Support\Redis\SwooleRedisPool" not found`

- [ ] **Step 3: Write minimal implementation** (`app/Support/Redis/SwooleRedisPool.php`)

```php
<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Redis;
use RuntimeException;
use Swoole\Coroutine\Channel;

/**
 * Pool de conexoes phpredis por worker para uso sob coroutines do Swoole.
 * Espelha App\Support\Database\SwoolePdoPool: cada coroutine recebe uma
 * conexao propria, emprestada/devolvida via Channel (coroutine-safe). Uma
 * instancia por conexao logica (default db0, cache db1).
 */
final class SwooleRedisPool
{
    private Channel $channel;

    private int $created = 0;

    /** @param callable():object $factory cria uma conexao (\Redis em producao) */
    private function __construct(
        private $factory,
        private readonly int $size,
        private readonly float $timeout,
    ) {
        $this->channel = new Channel($size);
    }

    /** Pool a partir de uma conexao redis do config/database.php (TLS Azure). */
    public static function fromConnection(string $connection = 'default', int $size = 16, float $timeout = 3.0): self
    {
        $cfg = config("database.redis.{$connection}");
        if (! is_array($cfg)) {
            throw new RuntimeException("SwooleRedisPool: conexao redis '{$connection}' invalida.");
        }
        $scheme = $cfg['scheme'] ?? 'tcp';
        $host = ($scheme === 'tls' ? 'tls://' : '').($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 6379);
        $password = $cfg['password'] ?? null;
        $db = (int) ($cfg['database'] ?? 0);

        $factory = static function () use ($host, $port, $password, $db): Redis {
            $r = new Redis();
            $r->connect($host, $port, 2.0);
            if ($password) {
                $r->auth($password);
            }
            $r->select($db);
            return $r;
        };

        return new self($factory, max(1, $size), $timeout);
    }

    /** Apenas testes: injeta a fabrica (sem Redis real). */
    public static function forTesting(int $size, float $timeout, callable $factory): self
    {
        return new self($factory, $size, $timeout);
    }

    public function available(): int
    {
        return $this->channel->length();
    }

    public function run(callable $fn): mixed
    {
        $conn = $this->acquire();
        try {
            $result = $fn($conn);
        } catch (\RedisException $e) {
            $this->discard();      // conexao morta (SSL reset / idle Azure)
            throw $e;
        } catch (\Throwable $e) {
            $this->release($conn);
            throw $e;
        }
        $this->release($conn);

        return $result;
    }

    public function acquire(): object
    {
        if ($this->created < $this->size && $this->channel->isEmpty()) {
            $this->created++;
            try {
                return ($this->factory)();
            } catch (\Throwable $e) {
                $this->created--;
                throw $e;
            }
        }
        $conn = $this->channel->pop($this->timeout);
        if ($conn === false) {
            throw new RuntimeException('SwooleRedisPool esgotado (timeout no acquire).');
        }
        return $conn;
    }

    public function release(object $conn): void
    {
        $this->channel->push($conn);
    }

    public function discard(): void
    {
        // Conexao descartada (nao volta ao Channel): libera um slot p/ recriar.
        $this->created = max(0, $this->created - 1);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=SwooleRedisPoolTest`
Expected: PASS (ou SKIPPED se o container nao tiver ext-swoole; nesse caso rodar no build swoole).

- [ ] **Step 5: Commit (SÓ produção — regra 10)**

```bash
git add app/Support/Redis/SwooleRedisPool.php
git commit -m "✨ feat(swoole): SwooleRedisPool (pool phpredis-TLS por coroutine)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: CoroutineRedisManager

**Files:**
- Create: `app/Support/Redis/CoroutineRedisManager.php`
- Test (local, não commitar): `tests/Unit/Support/Redis/CoroutineRedisManagerTest.php`

**Interfaces:**
- Consumes: `SwooleRedisPool` (Task 1).
- Produces: `CoroutineRedisManager extends Illuminate\Redis\RedisManager` com `connection($name)` coroutine-aware e `releaseCoroutine(int $cid): void`.

- [ ] **Step 1: Write the failing test** — fallback fora de coroutine delega ao pai

```php
<?php

namespace Tests\Unit\Support\Redis;

use App\Support\Redis\CoroutineRedisManager;
use Illuminate\Redis\Connections\Connection;
use Tests\TestCase;

final class CoroutineRedisManagerTest extends TestCase
{
    public function test_fora_de_coroutine_delega_ao_redismanager_padrao(): void
    {
        // Sem coroutine ativa (cid <= 0) -> comportamento padrao do Laravel.
        $mgr = new CoroutineRedisManager($this->app, 'phpredis', config('database.redis'));

        $conn = $mgr->connection('default');

        $this->assertInstanceOf(Connection::class, $conn);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CoroutineRedisManagerTest`
Expected: FAIL — classe não encontrada.

- [ ] **Step 3: Write minimal implementation** (`app/Support/Redis/CoroutineRedisManager.php`)

```php
<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;

/**
 * RedisManager coroutine-aware: dentro de uma coroutine Swoole (com hooks on),
 * empresta uma conexao propria do SwooleRedisPool por Coroutine::getCid(),
 * isolando o socket entre coroutines concorrentes. Fora de coroutine / hooks
 * off, delega ao RedisManager padrao (cache $connections[] do framework).
 */
final class CoroutineRedisManager extends RedisManager
{
    /** @var array<int,array<string,Connection>> cid => name => Connection */
    private array $coroutineConnections = [];

    /** @var array<string,SwooleRedisPool> name => pool */
    private array $pools = [];

    public function registerPool(string $name, SwooleRedisPool $pool): void
    {
        $this->pools[$name] = $pool;
    }

    public function connection($name = null)
    {
        $name = $name ?: 'default';

        $cid = $this->coroutineId();
        if ($cid > 0 && isset($this->pools[$name])) {
            if (! isset($this->coroutineConnections[$cid][$name])) {
                // Empresta \Redis cru do pool e embrulha na Connection do Laravel
                // (mesmo connector usado pelo framework), preservando a API
                // de Cache/Session/spatie sem mudar codigo de app.
                $client = $this->pools[$name]->acquire();
                $this->coroutineConnections[$cid][$name] = $this->wrap($name, $client);
            }
            return $this->coroutineConnections[$cid][$name];
        }

        return parent::connection($name);
    }

    /** Devolve ao pool todas as conexoes do cid (chamar no RequestTerminated). */
    public function releaseCoroutine(int $cid): void
    {
        foreach ($this->coroutineConnections[$cid] ?? [] as $name => $conn) {
            $this->pools[$name]->release($conn->client());
        }
        unset($this->coroutineConnections[$cid]);
    }

    private function coroutineId(): int
    {
        if (! extension_loaded('swoole') || ! class_exists(\Swoole\Coroutine::class)) {
            return -1;
        }
        return \Swoole\Coroutine::getCid();
    }

    /** Embrulha um \Redis cru numa PhpRedisConnection do Laravel. */
    private function wrap(string $name, object $client): Connection
    {
        $config = $this->config['connections'][$name] ?? ($this->config[$name] ?? []);

        return new \Illuminate\Redis\Connections\PhpRedisConnection(
            $client,
            null,
            $config,
            $this->config['options'] ?? []
        );
    }
}
```

> **Nota de verificação durante a implementação:** confirme no Laravel vendorizado a assinatura de `PhpRedisConnection::__construct` (`$client, $connector, $config, $options`) e como `Connection::client()` expõe o `\Redis`. Em versões em que `connection()` não recebe `?string`, ajuste o type-hint para casar com a classe pai (`#[\Override]` opcional). NÃO altere o contrato público (`connection`, `releaseCoroutine`).

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CoroutineRedisManagerTest`
Expected: PASS (fora de coroutine → delega ao pai → retorna `Connection`).

- [ ] **Step 5: Commit (SÓ produção)**

```bash
git add app/Support/Redis/CoroutineRedisManager.php
git commit -m "✨ feat(swoole): CoroutineRedisManager (bind Redis por-coroutine)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Wiring no OctaneServiceProvider

**Files:**
- Modify: `app/Providers/OctaneServiceProvider.php`

**Interfaces:**
- Consumes: `SwooleRedisPool` (Task 1), `CoroutineRedisManager` (Task 2).
- Produces: binding `redis` substituído sob Swoole; pools criados no `WorkerStarting`; `releaseCoroutine` no `RequestTerminated`.

- [ ] **Step 1: Bind do manager no `register()`** (só sob Swoole) — adicionar dentro do `register()` existente, após o guard `isRunningInOctane()`:

```php
// Sob Swoole, troca o RedisManager por um coroutine-aware. Fora do Swoole,
// nao mexe (deixa o RedisManager padrao do framework).
if ($this->isSwoole()) {
    $this->app->singleton('redis', function ($app) {
        $config = $app['config']['database.redis'] ?? [];
        $client = $config['client'] ?? 'phpredis';
        return new \App\Support\Redis\CoroutineRedisManager($app, $client, $config);
    });
    $this->app->alias('redis', \Illuminate\Contracts\Redis\Factory::class);
}
```

- [ ] **Step 2: Criar os pools no `WorkerStarting`** — no listener `WorkerStarting` existente (junto do `bootSwoolePdoPool()`), adicionar:

```php
$this->bootSwooleRedisPools();
```

E o método (mesmo arquivo), seguindo o padrão guardado do `bootSwoolePdoPool()`:

```php
protected function bootSwooleRedisPools(): void
{
    if (! $this->isSwoole()) {
        return;
    }
    try {
        $manager = $this->app->make('redis');
        if (! $manager instanceof \App\Support\Redis\CoroutineRedisManager) {
            return;
        }
        $size = (int) env('OCTANE_REDIS_POOL_SIZE', 16);
        $timeout = (float) env('OCTANE_REDIS_POOL_TIMEOUT', 3.0);
        foreach (['default', 'cache'] as $name) {
            $manager->registerPool(
                $name,
                \App\Support\Redis\SwooleRedisPool::fromConnection($name, $size, $timeout)
            );
        }
    } catch (\Throwable $e) {
        // Falha do pool nunca derruba o worker (degrada para o caminho padrao).
        report($e);
    }
}
```

- [ ] **Step 3: Devolver conexões no `RequestTerminated`** — no listener `RequestTerminated` existente, adicionar:

```php
if ($this->isSwoole() && ($cid = \Swoole\Coroutine::getCid()) > 0) {
    $mgr = $this->app->make('redis');
    if ($mgr instanceof \App\Support\Redis\CoroutineRedisManager) {
        $mgr->releaseCoroutine($cid);
    }
}
```

- [ ] **Step 4: Verificação estática + boot local**

Run: `docker exec newsdc_frankenphp_local php -l app/Providers/OctaneServiceProvider.php`
Expected: `No syntax errors detected`

Run (fora do Swoole, confirma que nada quebrou): `docker exec newsdc_frankenphp_local php artisan config:clear && docker exec newsdc_frankenphp_local php artisan route:list --name=login`
Expected: lista a rota sem erro (o bind só age sob Swoole; em FrankenPHP segue padrão).

- [ ] **Step 5: Commit (SÓ produção)**

```bash
git add app/Providers/OctaneServiceProvider.php
git commit -m "✨ feat(swoole): wire RedisPool por-coroutine no OctaneServiceProvider

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Validação sob carga + ligar hooks (gate de produção)

**Files:**
- Nenhum arquivo de produção; muda env no App Service (`OCTANE_HOOK_FLAGS_ENABLED=true`).

**Interfaces:**
- Consumes: Tasks 1-3 já no deploy (imagem swoole rebuildada com o pool).

- [x] **Step 0 (acrescentado): validação em DEV antes de tocar produção** — VALIDADO 2026-08-25

O plano original saltava de Tasks 1-3 direto para o gate de produção (Azure). Faltava reproduzir em dev a falha que causou o incidente. Harness CLI com hooks ON, `Cache`/`Redis` usados como o framework usa (implicitamente), N coroutines concorrentes:

| pool | conc | ok | bound | timeout | other |
|---|---|---|---|---|---|
| 16 | 40  | 40  | 0 | 0 | 0 |
| 16 | 100 | 100 | 0 | 0 | 0 |
| 4  | 100 | 100 | 0 | 0 | 0 |

CONTRA-PROVA (o que torna o verde confiável): o MESMO harness sem registrar os pools reproduz o erro do incidente, e como **Fatal error** — não exceção capturável, que é por que derrubava o worker:

```
Swoole\Error: Socket#32 has already been bound to another coroutine#23,
reading of the same socket in coroutine#4 at the same time is not allowed
  em Illuminate/Redis/Connections/Connection.php:122 (Redis->get)
  via Cache::get -> RedisStore::get
```

Confirma o diagnóstico do design §1: o Redis é usado implicitamente em todo request, e sem bind por-coroutine qualquer página real cai.

- [ ] **Step 1: Build + deploy da imagem com o pool** (mesmo fluxo do hotfix: build retry + push retry + cutover limpo via digest). Manter `OCTANE_HOOK_FLAGS_ENABLED=false` neste deploy.

- [ ] **Step 2: Ligar os hooks em produção**

Run: `az webapp config appsettings set -n sdcdefesa -g Defesa_Civil --settings OCTANE_HOOK_FLAGS_ENABLED=true`
(o appsettings set reinicia sozinho — NÃO dar restart extra; aguardar boot)

- [ ] **Step 3: Load-test de concorrência (gate)**

Run: 40+ requests concorrentes em `/` e `/login` (xargs -P 16), repetir 3x.
Expected: **0** respostas 5xx; **0** ocorrências de `Socket ... bound to another coroutine` no log; latência estável; sem crash loop. Critério idêntico ao spec do `SwoolePdoPool`.

- [ ] **Step 4: Rollback imediato se falhar**

Run: `az webapp config appsettings set -n sdcdefesa -g Defesa_Civil --settings OCTANE_HOOK_FLAGS_ENABLED=false`
Expected: volta ao modo síncrono estável (sem o paralelismo), sem redeploy.

- [ ] **Step 5: Commit do ajuste de doc (se houver)** — registrar no `SWOOLE_PERFORMANCE.md` que a Fase 3 usa pool custom + bind transparente (em vez do `Swoole\Database\RedisPool` nativo). Commit `📝 docs`.

---

## Self-Review

- **Cobertura do spec:** SwooleRedisPool (Task 1) ✓ §4.1; CoroutineRedisManager + bind + nota coroutine aninhada (Task 2) ✓ §4.1; wiring boot/release (Task 3) ✓ §4.1/4.2; TLS/db_index/timeout/discard (Task 1 código) ✓ §5; ativação/rollback + load-test (Task 4) ✓ §6/§7; config env (Task 3 Step 2) ✓ §6.1.
- **Placeholders:** nenhum TBD; código completo em cada task; a única nota de verificação (assinatura do `PhpRedisConnection`) é instrução explícita, não placeholder.
- **Consistência de tipos:** `SwooleRedisPool::{fromConnection,run,acquire,release,discard,available}`, `CoroutineRedisManager::{connection,releaseCoroutine,registerPool}` usados de forma idêntica entre Task 1→2→3.
- **Regra 10:** os Steps de commit fazem `git add` apenas dos arquivos de produção; os testes ficam locais.
