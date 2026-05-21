# Plano de testes performáticos — série `feat/db-arquitetura-*`

## Contexto

A série de 6 branches entregou resiliência DB + foundation async + operação. Antes de declarar produção-ready, é preciso validar empiricamente que o sistema sustenta a meta declarada no spec original (**>1000 req/s em pico de crise**) e que cada componente cumpre seu papel sob carga.

Este plano define **17 cenários de teste** organizados por fase, com k6 scripts, métricas observadas, thresholds e critérios de sucesso. Scripts vivem em `SDC/tests/load/` (não commitados, política do projeto). Plano commitado em `docs/` como referência operacional.

Pré-requisito: aplicar os fixes críticos do `hazy-watching-ritchie.md` ANTES de rodar perf tests — sem isso o teste do queue `bulk` (T6) falha imediatamente e mascara problemas downstream.

---

## Ambiente de teste

**Mínimo (smoke/dev):**
- Laravel Octane local (FrankenPHP 4 workers)
- PostgreSQL 18 PostGIS container (via `compose.dev.yml`)
- Redis 7 local
- k6 instalado (`choco install k6` / `brew install k6`)
- Composer install completo + migrations rodadas + seed mínimo (RAT, Decretacoes)

**Recomendado (representativo):**
- Staging com 3 replicas Octane (RoadRunner) + Redis + PG Flexible Server
- Pelo menos 10k linhas em `rat_ocorrencias` e 5k em `decretacoes_processos` para exports reais
- Prometheus + Grafana com scrape de `/api/metrics`
- Acesso ao `pg_stat_activity` para validar contagem real de conexões

**Crítico (load test final):**
- Staging idêntico a prod ou prod em horário não-pico
- Datasets reais (PowerBI exports, RAT historico)

---

## Métricas-chave (todas via `/api/metrics` Prometheus)

| Métrica | Threshold de alerta | Critério crítico |
|---|---|---|
| `sdc_db_slots_active / sdc_db_slots_limit` | > 0.8 sustained | nunca = limit |
| `sdc_db_circuit_breaker_state` | = 1 (half-open) > 1min | = 2 (open) sustained |
| `sdc_rate_limit_global_current` | > 1200 (80% threshold 1500) | trip threshold |
| `http_req_duration p95` | > 500ms | > 1500ms |
| `http_req_failed` | > 1% | > 5% |
| `pg_stat_activity_count` (via psql ou exporter) | > 80% max_connections | = max_connections |
| `php_memory_get_usage_peak_bytes` | > 256MB em job | > 512MB |
| RequestTrace `processing` count | > 50 stale > 30min | growing unbounded |

---

## 17 cenários organizados por fase

### Fase Pool (resiliência DB) — Testes 1-7

#### T1 — Baseline sustentado (1000 req/s × 10min em `/api/health`)

**Goal:** validar que a stack inteira sustenta a meta declarada sem degradação.

**k6 stage:** ramp 1min→200, 5min→1000, sustain 10min @ 1000, ramp-down 1min.

**Endpoint:** `GET /api/health` (passa por `Backpressure` + `AcquireConnectionSlot` + `ApiRateLimiter` + statement_timeout=2s).

**Métricas observadas:** todas listadas acima.

**Critério:** `http_req_failed < 1%`, p95 < 500ms, p99 < 1500ms, `sdc_db_slots_active` nunca passa 80% do limit, `circuit_breaker_state == 0` (closed) o tempo todo.

**Já existe:** `SDC/tests/load/k6-baseline.js` (untracked). Manter.

---

#### T2 — Spike picos curtos (1500 req/s × 2min, retorno a 0)

**Goal:** validar que `Backpressure` dropa tier público com 503 gracioso sem quebrar tier autenticado.

**k6 stage:** 30s ramp → 1500 sustain 2min → 30s ramp-down.

**Setup:** mix de tiers via header — 70% tier `public` (sem auth), 20% `free`, 10% `internal` (header `X-Tier: internal` simulado).

**Critério:** tier `public` recebe predominantemente 503 com `Retry-After` quando utilization > 70%. Tier `internal` mantém p95 < 800ms e 200 em todas as requests. `http_req_failed` global < 5% (esperado por causa do tier public dropado, mas com 503 graciosos, não 500/timeout).

---

#### T3 — Chaos Redis (rate limiter fail-closed)

**Goal:** validar que `ApiRateLimiter::checkGlobalBucket` fail-closed para tiers baixos quando Redis cai.

**Steps:**
1. Iniciar baseline T1 estabilizado.
2. Em t=3min, executar `docker stop newsdc_dev_redis` (ou equivalente staging).
3. Observar 30s.
4. `docker start newsdc_dev_redis`.
5. Observar recuperação 1min.

**Critério:**
- Imediatamente após kill: tier `public`/`free` retorna 503 `Service Degraded`. Tier `internal` continua 200.
- Após restart: sistema volta a aceitar todos tiers em < 60s.
- Zero 500 (todos 503 graciosos).
- `/api/health` reflete degradação durante o intervalo (`status: degraded`).

---

#### T4 — Chaos slow query (circuit breaker abre)

**Goal:** validar que `DatabaseCircuitBreaker` abre após 5 queries > 30s e fecha após reset_seconds=30.

**Setup:** rota de teste local `/api/test/slow-query?ms=35000` (criar temporariamente) que executa `DB::statement('SELECT pg_sleep(?)', [$ms/1000])`.

**Steps:**
1. Disparar 10 requests em sequência para essa rota.
2. Observar `sdc_db_circuit_breaker_state` mudar para 2 (open) após o 5º timeout.
3. Verificar que rotas `expensive`/`heavy` (via `Backpressure`) retornam 503 enquanto CB aberto.
4. Aguardar 35s (reset + margin).
5. Verificar transição para half-open (state=1) e depois closed (state=0) após próxima query rápida.

**Critério:** transições corretas, mensagem no log "Database circuit breaker tripped (OPEN)".

---

#### T5 — Tenancy isolation sob carga

**Goal:** validar que `SelectiveDisconnectFromDatabases` listener não vaza estado de tenant entre requests no mesmo worker Octane.

**Setup:** 2 tenants (A e B) com dados distintos. Requests alternadas tenant A → B → A → B com headers/subdomínios diferentes.

**Steps:**
1. 1000 iterações de A/B alternados a 50 req/s.
2. Em cada response do tenant X, verificar que dados retornados pertencem a X (não vazaram do Y).

**Critério:** 100% das requests retornam apenas dados do tenant correspondente. `sdc_db_slots_active` estável (não cresce por causa de conexões `tenancy` acumuladas).

---

#### T6 — Queue `low` funciona (validação pós-fix CR2)

**Goal:** validar que após CR2 fix #1 os jobs assíncronos efetivamente entram na queue e são processados.

**Steps:**
1. Subir worker: `php artisan queue:work redis-low --tries=2 --timeout=600`.
2. Dispatch 100 async exports: loop `curl POST /api/v1/decretacoes/export/power-bi/async`.
3. Verificar `redis-cli LLEN queues:low` cresce.
4. Em ~5min, verificar todos os 100 traces transicionaram para `completed`.
5. `traces:status --type=export_decretacoes_powerbi` mostra contagem correta.

**Critério:** 100% completed. Zero traces em `pending` ou `processing` após 10min.

---

#### T7 — Persistent connection leak detection

**Goal:** validar que `ATTR_PERSISTENT=true` em `pgsql`/`pgsql_read` não causa vazamento de prepared statements ou de session settings entre requests.

**Steps:**
1. Baseline T1 estabilizado.
2. Em paralelo, monitor `pg_stat_activity` filtrado por `application_name='sdc-laravel'` a cada 10s.
3. Verificar `state_change` e `backend_xid` para detectar transações órfãs.
4. Após 30min, contar conexões backend distintas — deve estabilizar perto do n_workers × n_replicas.

**Critério:** zero conexões em `idle in transaction (aborted)`. Zero prepared statements órfãos (`select count(*) from pg_prepared_statements` estável).

---

### Fase F1 (async foundation) — Testes 8-10

#### T8 — Throughput de dispatch e criação de trace

**Goal:** medir custo de `dispatchAsyncJob` sob carga.

**Setup:** rota `/api/v1/test/dispatch` (criar temporário) que chama `$this->dispatchAsyncJob(NoopJob::class, 'test', [], [], 'low', 5)`.

**Steps:**
1. k6 stage 30s @ 100 req/s nesse endpoint.
2. Verificar criação de `request_traces`.

**Critério:** p95 do dispatch < 50ms. 100% dos traces criados. `RequestTrace::count() == n_requests`.

---

#### T9 — Polling concorrente do TraceController

**Goal:** validar que `/api/v1/traces/{id}` aguenta polling intensivo sem degradar o sistema.

**Steps:**
1. Criar 50 traces (via T8 ou semelhante).
2. k6 stage com 50 VUs cada um pollando seu trace a 1 req/s por 5min (50 req/s total).

**Critério:** p95 < 100ms. Zero erros. Sem aumento sustentado de `sdc_db_slots_active`.

---

#### T10 — Download concorrente de artefatos

**Goal:** validar que streaming via `Storage::disk('exports')->download()` aguenta concorrência.

**Setup:** 100 traces completed com arquivos de 10MB cada no disk `exports`.

**Steps:** k6 com 100 VUs baixando seus arquivos simultaneamente.

**Critério:** todos downloads completam, zero 5xx. Disk IO não vira gargalo (latência inicial < 200ms).

---

### Fase F2 (RAT export async) — Testes 11-12

#### T11 — 50 RAT exports concorrentes

**Goal:** validar que `ExportRatToCsvJob` com `tempnam` mantém memória estável sob concorrência.

**Steps:**
1. Subir 3 workers `php artisan queue:work redis-low`.
2. Dispatch 50 exports via `/rat/export/async`.
3. Monitor memória de cada worker (`ps aux | grep queue:work`).

**Critério:** memória peak de cada worker < 256MB. Tempo médio por job < 90s para dataset de 5k linhas. Zero arquivos órfãos em `tempnam` (sys_get_temp_dir limpo após cada job).

---

#### T12 — Export RAT volume real (10k+ linhas)

**Goal:** validar duração e memória de um único export grande.

**Setup:** RAT seed com 20k ocorrências, cada uma com `relatosMorph` populados.

**Steps:**
1. Dispatch 1 export sem filtros.
2. Aguardar completed.

**Critério:** completed em < 5min. Memória peak do worker < 256MB. Arquivo CSV gerado bate com `COUNT(*) FROM rat_ocorrencias`.

---

### Fase F3 (operação) — Testes 13-14

#### T13 — CleanupOldExportsCommand com 100k+ traces

**Goal:** validar performance do cleanup sob volume realista.

**Setup:** seed 100k `RequestTrace` em `completed`, 50% com `completed_at` > 7 dias atrás (elegíveis), 50% recentes.

**Steps:**
1. Rodar `time php artisan exports:cleanup --days=7`.
2. Medir duração e rows afetadas.

**Critério (depende de N1 do plano de fixes — índice em completed_at):**
- Sem o índice: > 60s, full scan.
- Com o índice: < 10s.

Documenta motivação para aplicar N1 antes desse teste.

---

#### T14 — TracesStatusCommand performance

**Goal:** validar que comando CLI não causa lock em tabela grande.

**Steps:** com mesmo seed do T13, rodar `time php artisan traces:status --since=30d`.

**Critério:** < 5s execução. Sem bloqueio de outros writes (verificar via `pg_locks` em paralelo).

---

### Fase F4 (notificação) — Testes 15-16

#### T15 — Burst de completions notification dispatch

**Goal:** validar que dispatch de notificação não bloqueia o job principal.

**Setup:** 50 jobs concorrentes completando próximos no tempo.

**Steps:**
1. Dispatch 50 RAT exports.
2. Quando todos completarem (~2min), medir lag entre `trace.completed_at` e notification visível no `notifications` table do user.

**Critério:** lag < 5s p95. Zero notificações perdidas (50 traces × 50 notifications = 2500 esperadas se 50 users).

---

#### T16 — Broadcast websocket capacity

**Goal:** validar que canal broadcast suporta N conexões simultâneas recebendo notification.

**Setup:** N=200 clients WebSocket conectados (k6 ws ou laravel-echo-server stress).

**Steps:**
1. Dispatch 1 notification para canal global.
2. Medir delivery time e drop rate.

**Critério (depende do driver broadcast — Pusher, Reverb, etc.):** delivery p95 < 2s. Drop rate < 1%.

**Nota:** este teste pode ser pulado se o projeto ainda não tiver broadcast configurado em prod.

---

### Cross-cutting — Test 17

#### T17 — End-to-end full pipeline

**Goal:** validar a jornada completa do usuário com async export.

**Steps:**
1. Cliente faz `POST /api/v1/decretacoes/export/power-bi/async` → recebe 202+trace_id.
2. Cliente faz polling `GET /api/v1/traces/{traceId}` a cada 2s.
3. Quando `status=completed`, cliente faz `GET /api/v1/traces/{traceId}/download` → recebe arquivo CSV.
4. Em paralelo, broadcast push é recebido pelo websocket do user.

**Critério:**
- Tempo total (dispatch → download disponível) < 90s para dataset médio (~5k linhas).
- Notification push recebida antes do polling next detectar completed.
- Arquivo baixado bate com expected dataset.

---

## Sequência de execução recomendada

### Antes de push para `origin/dev`

Smoke tests rápidos em dev local:
- **T6** (queue funciona) — bloqueante, falha se cr2 #1 não aplicado.
- **T8** (dispatch throughput) — validação básica do trait.
- **T17** (end-to-end) — fluxo principal funcional.

### Antes de PR para `main`

Suite média em staging:
- **T1** (baseline 1000 req/s)
- **T2** (spike)
- **T3** (chaos Redis)
- **T4** (chaos slow query)
- **T5** (tenancy isolation)
- **T11** (50 concurrent RAT exports)

### Antes de release production

Suite completa em staging-prod-like:
- Todos os 17 testes.
- Resultados anexados ao PR de release.

---

## Reporting

Para cada execução, gerar relatório com:

1. **Sumário k6** (`--out json=resultado.json`).
2. **Snapshot de `/api/metrics`** antes/durante/depois (curl + diff).
3. **Snapshot de `pg_stat_activity`** filtrado por `application_name`.
4. **Logs de `circuit_breaker tripped`** se houver.
5. **`traces:status --since=<duração do teste>`** ao final.

Salvar em `SDC/tests/load/reports/YYYY-MM-DD-tN-descricao.md` (gitignored).

---

## Critical files for implementation (scripts k6)

Scripts novos a criar em `SDC/tests/load/` (não commitar):

- `k6-baseline.js` (já existe — T1)
- `k6-spike.js` (já existe — T2, expandir com tier mix)
- `k6-chaos-redis.js` (novo — T3)
- `k6-chaos-slow-query.js` (novo — T4)
- `k6-tenancy-isolation.js` (novo — T5)
- `k6-queue-throughput.js` (novo — T6)
- `k6-dispatch-throughput.js` (novo — T8)
- `k6-trace-polling.js` (novo — T9)
- `k6-trace-download.js` (novo — T10)
- `k6-rat-concurrent.js` (novo — T11)
- `k6-end-to-end.js` (novo — T17)

Scripts ad-hoc (artisan / bash):
- `rat-large-export.sh` (T12)
- `seed-traces-100k.php` (T13/T14 setup)
- `notification-burst.sh` (T15)

---

## Dependências externas

- **Datasets seedados:** Decretacoes (5k+), RAT (10k+), Users com tenants.
- **Workers configurados:** `php artisan queue:work redis-low` (após fix CR2 #2).
- **Broadcast driver:** se T16 for executado, configurar Reverb/Pusher.
- **Acesso a `pg_stat_activity`:** user de DB com `pg_read_all_settings` ou `pg_monitor`.

---

## Riscos operacionais

| Risco | Mitigação |
|---|---|
| Teste em prod por engano | k6 scripts validam `BASE_URL` é staging/dev antes de start |
| Seed gerar dados que vazam para prod | Marcador `is_test_data` em todos os modelos seed; comando de cleanup posterior |
| Chaos Redis interromper outros serviços | Rodar em isolated Redis instance (não compartilhada) |
| Job órfão consumindo workers em prod | Limit total de jobs no test = N pequeno; supervisor com `--stop-when-empty` |

---

## Confiança de release pós-execução

| Suite | Confiança |
|---|---|
| Smoke (T6+T8+T17) passa | OK push origin/dev |
| Média (T1-T5, T11) passa | OK PR main |
| Completa (todos 17) passa | OK release production |
| Falha em T1 (baseline) | BLOQUEAR release |
| Falha em T3/T4 (chaos) | BLOQUEAR PR main |

Suite completa = janela de 3-4 horas de execução em staging dedicado.
