# Design — Consumo do SwoolePdoPool (doc #3)

**Data:** 2026-06-15
**Branch base:** `feat/coroutine-request-isolation` (Fases 1–2 já mergeáveis)
**Decisão (2026-06-15):** **B2 — helper explícito (`App\Support\Concurrency`).** B1 (transparente no Eloquent) fica **documentado como evolução futura**, condicionado à medição pós-B2. Alvos de deploy: **Azure App Service E on-premise**.

---

## 1. Problema

Sob `OCTANE_SERVER=swoole` com `enable_coroutine` + `SWOOLE_HOOK_ALL`, várias requests intercalam como coroutines no mesmo worker. O Eloquent usa **uma conexão PDO por worker** (singleton no `DatabaseManager`). Uma conexão PostgreSQL é request-resposta: **não multiplexa** duas queries simultâneas. Duas coroutines usando o mesmo PDO → bloqueio/erro de protocolo.

**Evidência empírica (load-test de 2026-06-15, sonda efêmera `/_tenancy_probe`):**

| Concorrência | Sucessos | Timeouts (ETIMEDOUT) | Vazamento cross-tenant |
|---|---|---|---|
| 8 simultâneas | 471/500 | 0 | 0 |
| 25 simultâneas | 328/600 | 272 | 0 |

A correção de isolamento (Fase 2) está **correta** (0 vazamentos), mas acima de ~8 coroutines concorrentes por worker as queries **serializam/estouram timeout** na conexão única. Esse é o gargalo que o doc `SWOOLE_PERFORMANCE.md` §3 prevê.

## 2. Estado atual (o que já existe)

- **`app/Support/Database/SwoolePdoPool.php`** — pool completo e correto: monta DSN pgsql com `sslmode`/`sslrootcert` (TLS Azure), remove `ATTR_PERSISTENT`, empresta/devolve PDO via `Swoole\Coroutine\Channel`, faz rollback de transação suja na devolução, descarta conexão morta e corrige o contador de slots. API: `run(callable $fn): mixed`.
- **`app/Providers/OctaneServiceProvider.php`** — cria `swoole.pgsql.pool` como singleton por worker no `WorkerStarting`, só sob Swoole; inerte em RoadRunner. Falha do pool nunca derruba o worker.
- **`config/octane.php`** — opções Swoole (coroutine, hook_flags, task workers, reuse_port, max_request) já calibradas.

**O que falta (o gap):** **nada consome o pool.** Todo o código de aplicação (controllers, services, módulos) usa o Eloquent normal → conexão única → a contenção permanece. O comentário no provider referencia um `App\Support\Concurrency` que **não existe**.

## 3. Restrições e premissas

- **Modelo A (single-DB):** isolamento por `tenant_id` (trait `HasTenant`), sem troca de conexão por request. O pool é de UMA conexão lógica (`pgsql`).
- **TLS Azure obrigatório:** o pool já monta o DSN com SSL; precisa ser **validado sob carga em build Swoole real** (a própria classe marca isso como limitação não confirmada).
- **Eloquent deve continuar funcionando** para todo o código existente.
- **Fallback:** sob RoadRunner/FrankenPHP/CLI (sem ext-swoole), o caminho normal do Laravel deve permanecer intacto.
- **Outras conexões** (`tenancy` legado, `webhook`, `pgsql_read`, `legacy`, `carga`) ficam **fora de escopo** nesta fase — só `pgsql` (a quente do tráfego web).
- **Transações:** uma request HTTP = uma coroutine; uma transação deve usar a MESMA conexão do início ao fim.

### 3.1 Alvos de deploy — Azure E on-premise

O pool deve ser **agnóstico de ambiente**, dirigido só por env (o `SwoolePdoPool` já monta o DSN a partir do `config/database`, então nada hard-coded). Os dois alvos diferem em:

| Aspecto | Azure App Service | On-premise |
|---|---|---|
| TLS | obrigatório (`DB_SSLMODE=require`, porta 5432/SSL) | geralmente LAN; `DB_SSLMODE=prefer/disable`, sem `sslrootcert` |
| `max_connections` do Postgres | gerenciado, tipicamente alto | **frequentemente menor** (hardware do órgão); teto mais sensível |
| Latência DB (I/O) | ~rede de nuvem | LAN baixa → razão I/O:CPU menor → ganho de coroutine menor, mas pool ainda elimina a contenção e o handshake |
| Workers/instâncias | autoscale por tier (B1→S3) | nº fixo conforme a máquina |

**Implicações de design:**
- O DSN do pool **não pode assumir TLS**: quando `sslmode` for `disable`/`prefer` e não houver `sslrootcert`, o DSN omite esses campos (o `SwoolePdoPool::fromConnection` já faz isso condicionalmente — confirmar no plano).
- **Teto de conexões é a restrição dura on-premise:** `SWOOLE_PG_POOL_SIZE × workers × instâncias ≤ max_connections − reserva`. Em on-premise modesto, `pool_size` provavelmente precisa ser menor que o default 16. Tornar `SWOOLE_PG_POOL_SIZE` configurável por ambiente (já é env) e **documentar a fórmula**.
- Validar o load-test do pool **nos dois alvos** (Azure staging + um Postgres on-premise/local representativo), não só na Azure.

## 4. Comparação — B1 vs B2 (referência; escolhido B2, ver §5)

### B1 — Integração transparente no Eloquent

**Ideia:** sob Swoole, a conexão `pgsql` do Laravel resolve seu PDO **por-coroutine** a partir do pool, transparente para todo o Eloquent.

**Arquitetura:**
- Driver/conexão custom registrado via `DatabaseManager::extend('pgsql', ...)` ou subclasse de `Illuminate\Database\PostgresConnection`.
- `getPdo()` retorna um PDO **coroutine-local** (guardado em `Swoole\Coroutine::getContext()`, mesmo padrão do `TenantContext` da Fase 2): emprestado do pool no **primeiro acesso** dentro da coroutine, **reusado** para todas as queries daquela coroutine (incl. transações), e **devolvido** ao pool no `RequestTerminated`.
- Fora de coroutine (RoadRunner) → comportamento padrão do Laravel (PDO de instância).

**Ciclo de vida:**
1. `RequestReceived` (coroutine nova) — nada (lazy).
2. 1ª query → `getPdo()` empresta do pool e fixa no contexto da coroutine.
3. Demais queries/transação → reusam o mesmo PDO do contexto.
4. `RequestTerminated` → devolve ao pool (rollback se transação aberta; descarta se morto).

**Pontos de risco (precisam de validação):**
- **Laravel cacheia o PDO em propriedade de instância** (`Connection::$pdo`) e vários métodos leem `$this->pdo` direto. Uma subclasse que sobrescreve só `getPdo()` pode não bastar — talvez seja preciso **trocar o objeto Connection por-coroutine** no resolver, não só o PDO. **Investigar no `writing-plans`.**
- Pinning de transação por coroutine (resolvido por "empresta 1x e reusa", mas precisa de teste).
- `lastInsertId`, prepared statements, `read/write` PDO — todos coroutine-local.
- TLS Azure sob carga real.

**Conserta:** TODO endpoint Eloquent, sem reescrever código de app. **Risco: ALTO.**

### B2 — Helper explícito (`App\Support\Concurrency`)

**Ideia:** completar o `App\Support\Concurrency` referenciado pelo provider, expondo o pool para hot paths que **optam** por usá-lo.

**Arquitetura:**
- `Concurrency::run(callable $fn)` → delega a `app('swoole.pgsql.pool')->run($fn)` (raw PDO); fora de Swoole, abre/fecha um PDO efêmero ou cai no `DB::connection()`.
- `Concurrency::parallel(array $closures)` → `Swoole\Coroutine\WaitGroup` rodando N closures concorrentes, cada uma com seu PDO do pool (doc §1 — queries paralelas dentro de um request).
- Hot paths (relatórios, dashboards, BI) reescritos como repositórios que usam o helper e retornam arrays.

**Conserta:** apenas os caminhos reescritos. **O Eloquent "normal" continua na conexão única** → a contenção geral (a que o load-test mostrou) **permanece** nos endpoints não migrados. **Risco: BAIXO.**

## 5. Decisão — B2 agora; B1 como evolução futura

**Escolhido: B2.** Completa o `App\Support\Concurrency` sobre o pool existente, valida o pool + TLS (Azure **e** on-premise) sob load-test real, e entrega o ganho do doc §1 (queries paralelas) nos hot paths — **sem** mexer no resolver de conexão do Laravel.

**B1 (transparente) fica documentado como evolução futura**, a ser reavaliada **só se** a medição pós-B2 mostrar que a contenção do Eloquent geral ainda é o gargalo do RPS-alvo. Não é escopo agora.

Racional: o maior risco não é o consumo — é confirmar que **PDO pgsql hookado + TLS** aguenta carga sob coroutine nos dois alvos (a própria `SwoolePdoPool` marca como não confirmado). B2 valida isso barato e reversível antes de qualquer custo/risco de B1.

## 6. Plano de teste

- **Reusar o harness de load-test** (sonda efêmera + cliente coroutine concorrente) já validado nesta sessão.
- **Métrica de capacidade:** repetir conc=25/50/100 e comparar timeouts ANTES (conexão única) vs DEPOIS (pool). Esperado: timeouts → ~0 enquanto `conc <= pool_size × workers`.
- **Métrica de correção:** manter o assert de 0 respostas cross-tenant (não regredir a Fase 2).
- **Transação:** teste que uma transação concorrente em coroutines distintas não vaza estado (commit/rollback isolados).
- **TLS Azure:** load-test no build Swoole apontando para o Postgres da Azure (staging), não só o Postgres local.
- Testes ficam **locais** (regra 10 do projeto: não entram no commit).

## 7. Questões em aberto

1. **Tamanho do pool** vs `max_connections` (ver §3.1): definir a fórmula `pool_size × workers × instâncias ≤ max_connections − reserva` e os valores default por alvo (Azure vs on-premise modesto). **Restrição dura on-premise.**
2. `pgsql_read` (IA/BI) também ganha helper de pool, ou fica fora desta fase?
3. Quais hot paths reescrever primeiro (relatórios/dashboards/BI com queries paralelas) — listar os 2–3 de maior impacto.
4. Métrica de aceitação de RPS (por alvo) antes de trocar `OCTANE_SERVER=swoole` em produção.
5. **(B1, futuro)** subclasse de `PostgresConnection` basta, ou é preciso trocar o objeto `Connection` por-coroutine no resolver? — só relevante se B1 for retomado.

## 8. Fora de escopo (YAGNI)

- Pool para `tenancy`/`legacy`/`carga`/`webhook` (conexões frias/raras).
- Demais otimizações do doc (Task Workers, Atomic, Swoole\Table, JIT/preload) — fases próprias.
- Trocar o servidor de produção para Swoole (decisão de deploy, posterior à validação).
