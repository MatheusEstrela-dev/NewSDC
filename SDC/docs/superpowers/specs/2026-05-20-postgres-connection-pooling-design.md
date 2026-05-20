# Resiliência de banco e API para NewSDC — Design

**Data:** 2026-05-20
**Autor:** Matheus Estrela
**Branch alvo:** feat/db-resilience-software-defined
**Status:** Aprovado para implementação por fases

## 1. Contexto e problema

O sistema NewSDC opera 24/7 com criticidade alta (Defesa Civil de MG). Em pico de crise climática a meta é suportar **>1000 req/s** sem cascata de falhas. A arquitetura atual tem:

- Laravel + Octane (RoadRunner em prod, FrankenPHP em dev), 3 réplicas × 4 workers + 2 queue workers + 1 scheduler.
- PostgreSQL 17 em Azure Flexible Server (produção) e PostGIS 18 em container Docker (dev).
- 6 conexões nomeadas em `config/database.php` (`pgsql`, `pgsql_read`, `tenancy`, `legacy`, `carga`, `mysql`).
- Multi-tenancy dinâmica via `SetTenant` middleware.
- Listener `DisconnectFromDatabases` ativo em `OperationTerminated` (anula o ganho do Octane).
- `ApiRateLimiter` contextual em Redis, `CircuitBreakerService` para webhooks outbound, processamento assíncrono fire-and-forget de webhooks inbound.

### Sintoma esperado em pico (cenário a evitar)

- Estouro de `max_connections` do PG (independente do tier).
- Overhead de 25-60ms por request (TCP+SSL+fork de backend PG).
- Queries lentas (PostGIS) prendem conexão indefinidamente.
- Transações órfãs prendem locks.
- Rate limiter fail-open em queda do Redis = app sem freio.
- Cascata: app trava → healthcheck Traefik falha → restart → tempestade de reconexão.

## 2. Princípio orientador

**Resiliência mora no código. Infra é reforço opcional.**

A aplicação **deve performar bem em qualquer ambiente** — laptop do dev, Azure managed, AWS, on-prem, container minimal. Não deve depender de PgBouncer estar presente, nem de tier específico, nem de feature gerenciada. Todas as defesas críticas são padrões dentro do código Laravel/PHP.

Infra externa (PgBouncer sidecar, ProxySQL, réplicas de leitura) entra apenas como **reforço opcional** quando o ambiente permitir — nunca como dependência. Critério de sucesso: a app sustenta a carga-alvo **sem o reforço de infra**, só com os padrões de código.

## 3. Defesas de código (núcleo)

Estes são os padrões implementados no Laravel/PHP. Ordem aproximada de impacto.

### 3.1 Listener seletivo do Octane

**Arquivo novo:** `app/Listeners/Octane/SelectiveDisconnectFromDatabases.php`

Substitui `Laravel\Octane\Listeners\DisconnectFromDatabases`. Desconecta **apenas** conexões voláteis (`tenancy`, `legacy`, `carga`) ao fim de cada request. Mantém `pgsql`, `pgsql_read` persistentes dentro do worker do Octane.

```php
namespace App\Listeners\Octane;

use Illuminate\Database\DatabaseManager;

class SelectiveDisconnectFromDatabases
{
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

Em `config/octane.php`: trocar `DisconnectFromDatabases::class` por essa classe nova no listener de `OperationTerminated`.

**Ganho:** elimina overhead de handshake/SSL/fork PG nas conexões web principais. Sozinho corta ~30ms por request.

### 3.2 Semáforo de conexões em Redis (PgBouncer em PHP)

**Arquivo novo:** `app/Database/ConnectionSemaphore.php` + middleware `app/Http/Middleware/AcquireConnectionSlot.php`.

Conta concorrência global de queries em Redis. Limite configurável (`DB_MAX_CONCURRENT=100`). Adquire slot antes de cada request acessar DB; libera no `terminate` da request.

```php
class ConnectionSemaphore
{
    public function __construct(
        private \Redis $redis,
        private int $limit,
        private int $waitMs = 100,
        private int $maxWaitMs = 2000,
    ) {}

    public function acquire(string $owner): bool
    {
        $start = microtime(true);
        while ((microtime(true) - $start) * 1000 < $this->maxWaitMs) {
            $current = $this->redis->incr('db:slots:active');
            if ($current <= $this->limit) {
                $this->redis->sAdd('db:slots:owners', $owner);
                return true;
            }
            $this->redis->decr('db:slots:active');
            usleep($this->waitMs * 1000);
        }
        return false;
    }

    public function release(string $owner): void
    {
        if ($this->redis->sRem('db:slots:owners', $owner)) {
            $this->redis->decr('db:slots:active');
        }
    }
}
```

Middleware: se `acquire` falhar, retorna 503 com `Retry-After` (backpressure ANTES de tocar no DB).

**Importante:** o slot não é "1 por query" — é "1 por request ativa que pode usar DB". Workers Octane reusam o PDO persistente, então o semáforo limita requests concorrentes, não conexões PDO.

**Ganho:** garantia matemática de que a app nunca passa de N requests concorrentes batendo DB simultaneamente, qualquer que seja o ambiente.

### 3.3 Statement timeout por contexto (middleware)

**Arquivo novo:** `app/Http/Middleware/SetStatementTimeout.php`.

Define `SET LOCAL statement_timeout` no início da request, baseado no grupo de rota. Funciona em qualquer PG, sem precisar mexer no servidor.

```php
class SetStatementTimeout
{
    public function handle(Request $request, Closure $next, int $ms = 10000): Response
    {
        DB::statement("SET LOCAL statement_timeout = {$ms}");
        DB::statement("SET LOCAL idle_in_transaction_session_timeout = 60000");
        return $next($request);
    }
}
```

Aplicação por grupo de rota:

| Grupo | Timeout |
|---|---|
| `/api/health/*` | 2s |
| `/api/v1/*` (autenticado) | 10s |
| `/api/v1/webhooks/*` | 15s |
| `/dashboard/*`, `/relatorios/*` | 30s (mas devem ser assíncronos — ver 3.6) |
| Queue jobs (export/import) | 120s (`SET LOCAL` no job) |

**Ganho:** queries lentas são abatidas automaticamente, liberando conexão. Sem precisar de admin no PG.

### 3.4 Circuit breaker de banco

**Arquivo novo:** `app/Services/Database/DatabaseCircuitBreaker.php`. Reusa o padrão de `App\Services\Webhook\CircuitBreakerService` (estados closed/open/half-open em Redis).

Métricas observadas:
- p95 de query time nos últimos 60s (via `DB::listen`).
- Contagem de `QueryException` com SQLSTATE `57014` (statement_timeout) nos últimos 60s.
- Tempo médio de `acquire()` do semáforo.

Critério de abertura:
- p95 > 500ms por 30s OU
- ≥5 timeouts em 60s OU
- `acquire()` médio > 200ms.

Comportamento:
- Rotas `expensive`/`heavy` (já classificadas em `ApiRateLimiter::getRouteCost`): 503 com `Retry-After: 30`.
- Rotas `light`/`normal`: passam.
- Webhooks inbound: passam (já são fire-and-forget enfileirados).

**Ganho:** quando o DB começa a sufocar, a app degrada graciosamente em vez de cair.

### 3.5 Backpressure middleware

**Arquivo novo:** `app/Http/Middleware/Backpressure.php`. Roda **antes** do `ApiRateLimiter`.

Lê snapshot do `DatabaseCircuitBreaker` + semáforo. Se o sistema estiver sob estresse:
- Tier `public`: 503 imediato.
- Tier `free`/`default`: 503 se `acquire` falhar em 50ms.
- Tier `pro`/`enterprise`/`internal`: passa normalmente.

**Ganho:** sob ataque/tempestade legítima, derruba tráfego de menor valor **antes** de tocar no banco.

### 3.6 Resposta assíncrona obrigatória em rotas heavy

Auditar rotas marcadas `heavy`/`expensive` pelo `ApiRateLimiter::getRouteCost` (exports, relatórios, dashboards agregados, imports). Todas devem usar o trait `App\Http\Controllers\Traits\AsynchronousResponse` (já existe): enfileira job, responde 202 com `trace_id`, cliente faz polling.

Criar regra no `Pint`/code review: rotas com `cost >= 5` exigem `AsynchronousResponse`.

**Ganho:** worker Octane nunca fica preso por minutos. Pool de queue absorve picos.

### 3.7 Rate limiter fail-closed + bucket global

Modificar `app/Http/Middleware/ApiRateLimiter.php` ([linhas 140-148](../../../app/Http/Middleware/ApiRateLimiter.php)):

- **Fail-closed:** se Redis falhar, permitir apenas tiers `pro`, `enterprise`, `internal`, `webhook`. Recusar `public` e `free` com 503.
- **Bucket global:** novo contador `rate_limit:global:per_second` — quando passa de threshold (configurável, default 1500/s), recusa tiers `public`/`free`.

**Ganho:** em pico real, a defesa não vira esponja por causa de Redis sobrecarregado.

### 3.8 Read-through cache decorator

**Arquivo novo:** `app/Support/Cache/CachedRepository.php`. Decorator genérico para repositories de leitura intensiva.

Aplicar nos `Service`/`Repository` mais quentes:
- `OrgaoService`, `PrefeituraService` (lookups quase imutáveis): TTL 1h
- `PlanoContingenciaService`: TTL 15min
- `ProcessoStatsService` (já em warm): TTL 5min com tag-based invalidation

Invalidação por tag (Laravel Cache tags): write no model → flush tags relacionadas.

**Ganho:** 60-80% das queries de read repetitivo viram cache hit. Reduz pressão de pool diretamente.

### 3.9 Streaming de resultados (cursor)

Trocar `Model::all()` / `Model::get()` por `Model::cursor()` em todos os jobs ETL e exports que iteram grandes coleções:
- `IaIndexRats` (Console command)
- `MigrarCompdecLegadoCommand`
- Exports de processos/RATs/decretacoes
- Jobs de archive (incluindo o novo do 3.12)

**Ganho:** memória PHP estável; cursor PG libera linhas conforme consumidas; conexão segura mas com hold time previsível.

### 3.10 Query budget guard por request

**Arquivo novo:** `app/Database/QueryBudgetGuard.php`. Registra `DB::listen` em `Worker` boot. Conta queries por request. Em `RequestHandled`, log warning se > 30 queries (sinal de N+1). Em > 100 queries: log critical + retorna header `X-Query-Budget-Exceeded`.

**Ganho:** N+1 não passa silencioso. Endpoints "viralizam" antes de chegar em prod.

### 3.11 Conexão dedicada para webhooks (lógica, não infra)

**Em `config/database.php`:** adicionar conexão nomeada `pgsql_webhook` apontando para o **mesmo host PG**. A diferença é lógica: aplicação consciente roteia jobs de webhook nessa conexão. Combinado com o semáforo (3.2) configurado por conexão (semáforos separados), webhooks têm budget próprio e não esgotam o budget da web.

```php
'pgsql_webhook' => [
    // mesmas configs de pgsql, host idêntico
    // semáforo: DB_MAX_CONCURRENT_WEBHOOK=20
],
```

`ProcessWebhook` e `ProcessInboundWebhook` usam `DB::connection('pgsql_webhook')`.

**Ganho:** isolamento de domínio sem precisar de pooler externo.

### 3.12 Arquivamento de WebhookEvent (job agendado)

**Comando novo:** `app/Console/Commands/ArchiveWebhookEventsCommand.php`. Roda semanal via scheduler.

- Move `WebhookEvent` com `status='completed'` e `created_at < now() - 90 days` para `webhook_events_archive` (tabela com mesmo schema).
- Usa `cursor()` (padrão do 3.9).
- Log de quantidade arquivada.

**Ganho:** tabela operacional pequena → queries rápidas → conexões liberadas mais cedo. Trabalha em qualquer ambiente.

### 3.13 Cache de Swagger UI

L5-Swagger renderiza UI a cada request. Aplicar `cache.headers` em response middleware:

- `/api/documentation` e assets: cache 1h público.
- `/api/openapi.json`: cache 5min.
- Bypass via `?regenerate=1` somente para tier `internal`.

**Ganho:** Swagger não vira gargalo em ataque ou crawling.

## 4. Configurações Laravel

### 4.1 Mudanças em `config/database.php`

```php
'pgsql' => [
    // existente...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', true),
    ],
],
'pgsql_read' => [ /* mesmas regras */ ],
'pgsql_webhook' => [ /* host idêntico, semáforo próprio */ ],
```

### 4.2 Mudanças em `config/octane.php`

Trocar `DisconnectFromDatabases::class` por `App\Listeners\Octane\SelectiveDisconnectFromDatabases::class` no listener de `OperationTerminated`.

Pré-warm de bindings:
```php
'warm' => [
    ...Octane::defaultServicesToWarm(),
    App\Database\ConnectionSemaphore::class,
    App\Services\Database\DatabaseCircuitBreaker::class,
    App\Services\Webhook\CircuitBreakerService::class,
    // (já existentes)
],
```

### 4.3 Novo `config/resilience.php`

Concentrar todos os knobs num arquivo só. Tudo via env, default razoável.

```php
return [
    'db' => [
        'max_concurrent' => env('DB_MAX_CONCURRENT', 100),
        'max_concurrent_webhook' => env('DB_MAX_CONCURRENT_WEBHOOK', 20),
        'acquire_wait_ms' => env('DB_ACQUIRE_WAIT_MS', 2000),
        'circuit_breaker' => [
            'p95_threshold_ms' => env('DB_CB_P95_MS', 500),
            'timeout_count_threshold' => env('DB_CB_TIMEOUT_COUNT', 5),
            'reset_timeout_s' => env('DB_CB_RESET_S', 30),
        ],
    ],
    'rate_limit' => [
        'global_per_second' => env('RATE_LIMIT_GLOBAL', 1500),
        'fail_closed' => env('RATE_LIMIT_FAIL_CLOSED', true),
    ],
    'query_budget' => [
        'warn_at' => env('QUERY_BUDGET_WARN', 30),
        'fail_at' => env('QUERY_BUDGET_FAIL', 100),
    ],
];
```

### 4.4 Statement timeout por route group (`routes/api.php`)

```php
Route::middleware([SetStatementTimeout::class.':10000'])
    ->prefix('v1')
    ->group(function () {
        Route::middleware(SetStatementTimeout::class.':15000')
            ->group(fn() => require __DIR__.'/webhook_routes.php');
    });
```

### 4.5 Tuning PG opcional (server-side)

Se o ambiente permitir, **complementa** as defesas de código. Não é dependência:

```sql
ALTER SYSTEM SET statement_timeout = '30s';
ALTER SYSTEM SET idle_in_transaction_session_timeout = '60s';
ALTER SYSTEM SET log_min_duration_statement = 1000;
```

Funcionando ou não, as defesas em código continuam de pé.

## 5. Infra opcional (reforço, não dependência)

Se o ambiente de produção permitir e o orçamento justificar, infra externa amplifica as defesas:

| Reforço | Quando vale a pena | Status |
|---|---|---|
| PgBouncer sidecar (container) | Tráfego sustentado > 800 req/s comprovado em load test mesmo com as defesas de código | Opcional, postergável |
| Réplica de leitura PG | Workload de leitura > 70% e queries BI/PowerBI pesadas | Opcional, fora deste spec |
| Tier Azure maior (D8s_v3) | Métricas de produção indicarem >150 conn ativas sustentadas | Decidir com dados, não no plano |

Spec separado é criado quando/se os dados de produção indicarem necessidade. Este esforço **não pressupõe nenhum** deles.

## 6. Observabilidade

### 6.1 Métricas (Prometheus, expostas pela própria app)

Endpoint `/metrics` (Laravel `prom-client` ou similar). Métricas-chave produzidas em código:

- `sdc_db_slots_active` (semáforo)
- `sdc_db_acquire_wait_ms_p95`
- `sdc_db_query_duration_seconds_p95`
- `sdc_db_circuit_breaker_state` (gauge: 0=closed, 1=half-open, 2=open)
- `sdc_db_statement_timeout_total`
- `sdc_query_budget_warnings_total`
- `sdc_rate_limit_blocked_total{tier, reason}`
- `sdc_webhook_archive_rows_total`

### 6.2 Alertas (em `docker/monitoring/alerts/sdc-alerts.yml`)

- `DBCircuitBreakerOpen`: critical, 1min.
- `DBSemaphoreSaturated`: warning quando `slots_active / max_concurrent > 0.8` por 5min.
- `QueryBudgetExceededFrequently`: warning > 10/min.
- `RateLimitGlobalSaturated`: warning quando bucket global > 80%.
- `WebhookQueueDepthHigh`: critical quando `high` queue > 500 jobs.

### 6.3 Healthcheck enriquecido

`HealthCheckController` retorna:
- `db.semaphore_usage`
- `db.circuit_breaker_state`
- `redis.status`
- `queue.depth_by_priority`

Permite Traefik/loadbalancer rotear inteligentemente.

## 7. Plano de rollout por fases

Detalhado em `docs/superpowers/plans/2026-05-20-postgres-connection-pooling.md`.

Resumo (cada fase é gate de aprovação independente):

- **Fase 0 — Baseline + auditoria:**
  - Medir latência, conexões, p95/p99 em dev/staging.
  - Auditar rotas `heavy`/`expensive` quanto a uso de `AsynchronousResponse`.
  - Inventariar uso de `Model::all()`/`get()` em jobs ETL.

- **Fase 1 — Listener seletivo + persistência:** 3.1 + 4.1 + 4.2. Test E2E de tenancy isolation.

- **Fase 2 — Statement timeout por contexto:** 3.3 + 4.4. Teste rotas com query forçada > timeout.

- **Fase 3 — Semáforo + backpressure + circuit breaker:** 3.2 + 3.4 + 3.5 + 4.3. Chaos test: derrubar DB, validar 503 graciosos.

- **Fase 4 — Rate limit fail-closed + bucket global:** 3.7. Chaos test: derrubar Redis.

- **Fase 5 — Read-through cache + cursor streaming + query budget:** 3.8 + 3.9 + 3.10. Memory profile dos jobs ETL.

- **Fase 6 — Conexão webhook + arquivamento + Swagger cache:** 3.11 + 3.12 + 3.13.

- **Fase 7 — Resposta assíncrona obrigatória:** auditoria 3.6, refatorar rotas síncronas heavy.

- **Fase 8 — Observabilidade:** 6.1 + 6.2 + 6.3. Dashboards Grafana.

- **Fase 9 — Load test em staging:** k6 1000 req/s por 10min **sem nenhuma infra de reforço**. Critério de sucesso: p95 < 500ms, zero 5xx fora de 503 retry-after, semáforo nunca passa 80%.

- **Fase 10 — Rollout prod canário + monitoramento 24h.**

- **Fase 11 — Pós-rollout:** runbook, treinamento oncall, revisão de alertas.

## 8. Critérios de sucesso

- **Sustenta 1000 req/s por 10 min** em staging **sem PgBouncer e sem mexer no PG server**, só com defesas de código.
- **Zero vazamento de tenant** em testes de tenancy isolation (10k iterações alternando tenants).
- **p95 latência DB** ≤ 80ms.
- **Worker Octane nunca fica preso > 10s** em request síncrona.
- **Healthcheck reflete carga real** (não retorna 200 quando semáforo saturado).
- **Redis pode cair** sem derrubar a app (rate limiter fail-closed protege).
- **PG pode degradar** sem derrubar a app (circuit breaker abre, retorna 503 retry-after).

## 9. Riscos e mitigações

| Risco | Probabilidade | Mitigação |
|---|---|---|
| Listener seletivo vazar tenant | Crítico se falhar | Teste E2E 10k iterações alternando tenants; assertiva de `connection_id` distinto. |
| Semáforo Redis ficar inconsistente em crash | Baixo | TTL no contador (`db:slots:active` expira em 60s mesmo sem release); release no `terminate` + signal handler. |
| Statement timeout matar transação válida longa | Baixo | Jobs longos setam `SET LOCAL` próprio; valor padrão é por rota, não por conexão. |
| Backpressure derrubar tráfego legítimo demais | Médio | Tier `internal`/`enterprise` nunca é dropado; thresholds calibrados em load test. |
| Circuit breaker abrir/fechar em flapping | Médio | Janela de 30s + `reserve_pool` no half-open evita ping-pong. |
| Cache stale após write distribuído | Médio | Tag-based invalidation + TTL conservador; testes E2E de cache invalidation. |

## 10. Fora de escopo

- Réplica de leitura real do PG.
- Migrar legacy MySQL para PG.
- Sharding por tenant.
- PgBouncer sidecar em prod (decidir com dados pós-rollout).

## 11. Referências

- Laravel Octane connection lifecycle: `vendor/laravel/octane/src/Listeners/DisconnectFromDatabases.php`.
- ApiRateLimiter atual: `app/Http/Middleware/ApiRateLimiter.php`.
- CircuitBreakerService (webhook outbound, reusa-se o padrão): `app/Services/Webhook/CircuitBreakerService.php`.
- AsynchronousResponse trait: `app/Http/Controllers/Traits/AsynchronousResponse.php`.
- Spec relacionado (migração de SGBD que originou PG): `2026-04-27-mysql-postgres-newSDC-v2-design.md`.
