# Design — SwooleRedisPool coroutine-safe (Fase 3)

**Data:** 2026-06-17
**Branch base:** `feat/swoole-redispool` (a partir do hotfix `fix/swoole-prod-estabilizacao`)
**Decisão (2026-06-17):** **Híbrida** — pool custom TLS (espelha `SwoolePdoPool`) + **bind transparente** do Redis do framework por-coroutine. Alvos: Azure App Service E on-premise.

---

## 1. Problema

Sob `OCTANE_SERVER=swoole` com `enable_coroutine=true` + `SWOOLE_HOOK_ALL`, várias requests intercalam como coroutines no mesmo worker. As conexões Redis do Laravel são **singletons por worker** (`RedisManager` cacheia em `$connections[]`). Duas coroutines usando a MESMA conexão Redis colidem:

```
Swoole\Error: Socket#NN has already been bound to another coroutine#X,
reading of the same socket in coroutine#Y at the same time is not allowed
```

Diferente do PDO, o Redis é usado **implicitamente pelo framework em TODO request**: sessão (`CacheBasedSessionHandler`, `SESSION_DRIVER=redis`), cache (`CACHE_STORE=redis`), spatie-permission (`forgetCachedPermissions`), throttle. Por isso o crash derruba qualquer página real (só `/health` escapa, pois pula o middleware web). Também aparece `predis SSL reset errno=1017` (socket TLS corrompido por acesso concorrente).

**Mitigação atual (hotfix, já em prod):** `hook_flags=0` via `OCTANE_HOOK_FLAGS_ENABLED` (default false) — I/O bloqueante por worker, sem interleaving = estável, **mas sem paralelismo intra-worker**. Esta Fase 3 destrava os hooks com segurança.

## 2. Estado atual (o que já existe)

- `app/Support/Database/SwoolePdoPool.php` — pool PDO por worker (Channel, borrow/return, descarte de conexão morta, DSN TLS manual). Template direto para o Redis.
- `app/Providers/OctaneServiceProvider.php` — cria pools no `WorkerStarting` (só sob Swoole, guardado; falha não derruba worker), reseta/flush estado em `RequestReceived`/`RequestTerminated`.
- `config/octane.php` — `hook_flags` via `OCTANE_HOOK_FLAGS_ENABLED` (default off).
- `app/Database/ConnectionSemaphore.php` — type-hint `?Redis` (phpredis nativo); o pool DEVE entregar phpredis para não regredir.

**Gap do `SWOOLE_PERFORMANCE.md` §3:** propõe `Swoole\Database\RedisPool` nativo, consumido explicitamente — não cobre o Redis implícito do framework E provavelmente não faz **TLS** pro Redis da Azure (6380), o mesmo motivo que obrigou o `SwoolePdoPool` a montar conexão manual. Por isso usamos pool **custom**.

## 3. Restrições e premissas

- **TLS Azure obrigatório:** Redis em `newsdc.redis.cache.windows.net:6380`, scheme `tls`. O pool conecta phpredis com TLS.
- **phpredis (não predis):** `ConnectionSemaphore` exige `?Redis`; phpredis é o client nativo já usado.
- **Duas conexões lógicas:** `default` (db 0) e `cache` (db 1) — cada uma com seu pool.
- **Fallback:** sob FrankenPHP/RoadRunner/CLI/testes (sem coroutine), caminho normal do Laravel intacto.
- **Transação/multi-comando:** uma request = uma coroutine; a conexão emprestada serve a coroutine inteira (lease por-request, não por-comando).
- **Modelo A (single-DB):** isolamento por `tenant_id`; o pool não troca de conexão lógica por tenant.

## 4. Arquitetura

Pool de conexões phpredis-TLS por worker, emprestadas **por-coroutine** via `Swoole\Coroutine\Channel`, com **bind transparente**: o `RedisManager` resolve, para cada `Coroutine::getCid()`, uma conexão própria do pool. Framework inteiro fica coroutine-safe sem mudar código de aplicação.

### 4.1 Componentes (unidades isoladas)

- **`App\Support\Redis\SwooleRedisPool`**
  - O que faz: mantém N conexões phpredis-TLS de UMA conexão lógica (recebe `dbIndex` no construtor); `acquire()/release()/discard()` via `Channel`.
  - Como usar: `$pool->run(fn (\Redis $r) => $r->get($k))` (espelha API do `SwoolePdoPool`).
  - Depende de: `config('database.redis.<nome>')`, ext phpredis, ext swoole.
  - Conexão (phpredis TLS Azure): `new \Redis(['host' => 'tls://'.$host, 'port' => 6380, 'auth' => $password, 'database' => $dbIndex])` (ou `connect('tls://'.$host, 6380)` + `auth()` + `select($dbIndex)`). `select($dbIndex)` SEMPRE após conectar — `default`→db0, `cache`→db1.
  - `acquire()`: `Channel->pop($timeout)` com **timeout** (env `OCTANE_REDIS_POOL_TIMEOUT`, default 3.0s). Timeout → lança exceção tratável (não trava o worker). Cria sob demanda até `pool_size` (default 16, env `OCTANE_REDIS_POOL_SIZE`).
  - Resiliência: captura `\RedisException`/`\Throwable`; conexão morta (idle Azure / `SSL reset`) → `discard()` (não devolve socket quebrado, libera o slot). Antes de devolver ao pool, reset defensivo de estado pendente (`UNWATCH`/`DISCARD` se houve `MULTI`).

- **`App\Support\Redis\CoroutineRedisManager` (estende `Illuminate\Redis\RedisManager`)**
  - O que faz: `connection($name)` devolve a conexão **bindada ao cid atual**, emprestada do pool no 1º uso da coroutine; fora de coroutine OU com hooks off → `parent::connection()` (caminho padrão, cache `$connections[]`).
  - Estado interno: `private array $coroutineConnections = []` no formato `[cid][name] => \Redis`. Lógica de `connection($name)`:
    ```php
    if ($this->swooleHooksOn() && ($cid = Coroutine::getCid()) > 0) {
        $this->coroutineConnections[$cid][$name] ??= $this->poolFor($name)->acquire();
        return $this->coroutineConnections[$cid][$name];
    }
    return parent::connection($name);
    ```
  - Binding (em `OctaneServiceProvider::register`, só sob Swoole): `app()->singleton('redis', fn ($app) => new CoroutineRedisManager($app, $app['config']['database.redis']))` + `alias('redis', Illuminate\Contracts\Redis\Factory::class)`. Transparente p/ `Cache`/`Session`/spatie.
  - Depende de: `SwooleRedisPool` (um por conexão lógica: `default`, `cache`), `Coroutine::getCid()`.
  - **Coroutines aninhadas (gap conhecido):** `Concurrency::run`/`go()` criam um **cid novo** → não são cobertas pelo `RequestTerminated` da coroutine pai. **Fase 3 cobre só a coroutine principal do request.** Tarefas aninhadas que tocam Redis devem adquirir/devolver explicitamente (ou não usar Redis implícito). Liberação automática de cid aninhado fica como evolução futura.

- **Wiring em `OctaneServiceProvider`**
  - `WorkerStarting`: cria os pools (`default`, `cache`) — só sob Swoole, guardado; falha logada, nunca derruba worker.
  - `RequestTerminated`: devolve ao pool as conexões que a coroutine (cid) pegou; limpa o mapa cid→conexão.

### 4.2 Fluxo de dados (request sob hooks ON)

`StartSession`/`Cache`/spatie → `Redis::connection('cache')` → `CoroutineRedisManager` vê `cid=N` → empresta conexão exclusiva do pool (socket próprio, hookado) → comandos rodam sem colisão → `RequestTerminated` devolve a conexão ao pool.

## 5. Erros / resiliência

- Conexão morta (`SSL reset`/idle Azure) → `discard()` + recria; nunca devolve socket quebrado.
- Falha ao criar/popular pool no boot → try/catch + log; worker sobe no modo normal (degrada, não cai).
- Esgotamento do pool → `Channel->pop(timeout)`; timeout vira erro tratável (503 backpressure), não trava o worker.
- `ConnectionSemaphore` segue recebendo phpredis `?Redis`.

## 6. Ativação / rollback

- Pool + bind só agem sob Swoole. Ligar paralelismo: `OCTANE_HOOK_FLAGS_ENABLED=true` (já existe).
- Rollback sem redeploy: `OCTANE_HOOK_FLAGS_ENABLED=false` volta ao modo síncrono estável atual (a app não depende do pool quando hooks estão off — o bind por-coroutine é inerte fora de interleaving).

### 6.1 Configuração (env vars)

| Var | Default | Papel |
|---|---|---|
| `OCTANE_HOOK_FLAGS_ENABLED` | `false` | Liga `SWOOLE_HOOK_ALL` (paralelismo). Só ligar com o pool ativo e load-test verde. |
| `OCTANE_REDIS_POOL_SIZE` | `16` | Conexões por pool por worker. |
| `OCTANE_REDIS_POOL_TIMEOUT` | `3.0` | Timeout (s) do `Channel->pop()` no `acquire()`; estouro → exceção (503). |

Reuso de `REDIS_HOST/PORT/PASSWORD/SCHEME` já existentes (TLS 6380). O pool lê de `config('database.redis.<nome>')`.

## 7. Testes / critério de sucesso

- **Unit:** pool empresta/devolve/descarta; recriação após descarte; bind por-cid; **delegação a `parent::connection()`** quando fora de coroutine OU com hooks off; **timeout do `acquire()`** lança exceção (não trava) quando o pool esgota.
- **Integração (build Swoole real):** `Cache`/`Session`/spatie sob coroutines concorrentes não colidem.
- **Load test (como o spec do PdoPool):** ~25 coroutines concorrentes por worker → **0** ocorrências de `Socket ... bound to another coroutine`, **0** vazamento cross-tenant, latência estável, com `hook_flags=ON`. Critério de "pronto p/ ligar em prod".

## 8. Fora de escopo (otimização posterior — `SWOOLE_PERFORMANCE.md` §5/§6)

- Rate-limit → `Swoole\Atomic` (tira verificação de rate do Redis).
- Hot-cache → `Swoole\Table` (memória compartilhada entre workers).

Não são necessários para a correção (destravar hooks); entram como perf depois, medidos.
