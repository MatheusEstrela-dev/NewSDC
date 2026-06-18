# Design — PDO Pool transparente por-coroutine (B1)

**Data:** 2026-06-18
**Branch base:** `feat/swoole-redispool` (segue a Fase 3 do Redis; mesmo padrao de pool por-coroutine)
**Decisao:** **B1 (transparente)** — escolhido agora porque os hooks (`SWOOLE_HOOK_ALL`) ja estao LIGADOS em prod (Fase 3 Redis). O Eloquent geral ficou com risco latente de colisao de PDO entre coroutines; B1 fecha isso. Supera o B2 do doc #3 (`2026-06-15-swoole-pdopool-consumo-design.md`), que so protegia hot paths reescritos.

---

## 1. Problema

Com `enable_coroutine` + `SWOOLE_HOOK_ALL`, varias requests intercalam como coroutines no mesmo worker. O Eloquent usa **um objeto `Connection` (e um PDO) singleton por worker** (`DatabaseManager::$connections['pgsql']`). Duas coroutines na mesma conexao corrompem o protocolo pgsql (evidencia do doc #3: 25 concorrentes -> 272 timeouts) **e** compartilham o contador de transacao (`Connection::$transactions`), vazando estado de transacao entre requests.

Investigado no framework (Laravel 12): queries/transacoes passam por `getPdo()`/`getReadPdo()`, MAS o estado de transacao e prepared/lastInsertId vivem no **objeto `Connection`** (instancia). Logo, **nao basta** trocar so o PDO — e preciso uma **`Connection` por-coroutine**.

## 2. Restricoes

- **`max_connections` e o teto duro.** Hoje `Standard_B1ms` -> 50. Para pool util, escalar o SKU (cada conexao ~5-10MB de RAM): **B2s (~100)** recomendado; GP D2ds_v5 (~200+) p/ folga. Formula: `pool_size x workers x instancias <= max_connections - reserva`.
- **Transacao:** uma request (coroutine) = uma conexao do inicio ao fim (commit/rollback na MESMA).
- **Isolamento de tenant (Fase 2):** `TenantContext` por-coroutine NAO pode regredir (0 vazamento cross-tenant).
- **TLS Azure** (`DB_SSLMODE=require`) — o `SwoolePdoPool::fromConnection` ja monta o DSN com sslmode condicional.
- **Escopo:** somente a conexao `pgsql` (a quente do trafego web). `pgsql_read`/`tenancy`/`legacy`/`webhook`/`carga` fora (doc #3 §8).
- **Fallback:** fora de coroutine ou hooks off (FrankenPHP/RoadRunner/CLI/testes) -> Laravel padrao intacto.

## 3. Arquitetura

Espelha o Redis pool (Fase 3), mas entrega uma **`Connection` por-coroutine** (nao so PDO). Cada coroutine recebe, no 1o uso de DB, uma `PostgresConnection` propria cujo PDO e emprestado do `SwoolePdoPool`; a Connection fica em `Swoole\Coroutine::getContext()` (mesmo padrao do `TenantContext`); no `RequestTerminated`, rollback se houver transacao aberta e devolve o PDO ao pool.

### 3.1 Componentes (unidades isoladas)

- **`App\Support\Database\SwoolePdoPool`** (JA EXISTE) — reusado; entrega/recebe PDO via Channel, TLS, discard de morto. Adicionar `warm()` (igual ao Redis) p/ pre-criar no WorkerStarting.
- **`App\Support\Database\CoroutineConnectionFactory`** — cria uma `PostgresConnection` do Laravel a partir de um PDO emprestado, com o mesmo `config`/grammar/`reconnector` da conexao `pgsql`. Responsavel por embrulhar o PDO cru numa Connection valida (com QueryGrammar/PostProcessor de pgsql).
- **`App\Support\Database\CoroutineDatabaseManager` (estende `Illuminate\Database\DatabaseManager`)** — `connection('pgsql')`: sob hooks + em coroutine, retorna a Connection guardada em `getContext()` (cria via factory no 1o acesso, PDO do pool); senao, `parent::connection()`.
- **Wiring `OctaneServiceProvider`** — sob hooks: `extend('db', ...)` para o CoroutineDatabaseManager; `WorkerStarting` cria/`warm()` o `swoole.pgsql.pool`; `RequestTerminated` -> para o cid: se `transactionLevel()>0` faz rollback, devolve o PDO ao pool (discard se morto), limpa o contexto.

### 3.2 Fluxo (request sob hooks)

`SetTenant`/controllers/Eloquent -> `DB::connection('pgsql')` -> CoroutineDatabaseManager ve `cid>0` -> Connection do contexto (1o acesso: empresta PDO do pool + monta Connection) -> todas as queries/transacao do request reusam essa Connection -> `RequestTerminated` -> rollback-se-aberto + devolve PDO + limpa contexto.

## 4. Erros / resiliencia

- **Esgotamento do pool** (conc > size): `acquire` com timeout -> excecao -> tratada como **503 backpressure** (amarra no `Backpressure`/`ConnectionSemaphore` ja existentes). Nunca trava o worker.
- **Conexao morta** (idle/SSL reset): `discard()` + recria (padrao do pool).
- **Transacao aberta no fim do request:** rollback defensivo antes de devolver (evita vazar transacao p/ a proxima coroutine que pegar o PDO).
- **Falha ao montar pool/manager:** try/catch no boot -> cai no Laravel padrao (degrada, nao derruba).

## 5. Ativacao / rollback

- So age sob hooks (`hooksEnabled()`, mesmo gate do Redis). Hooks off -> DatabaseManager padrao.
- Rollback por env: `OCTANE_HOOK_FLAGS_ENABLED=false` (volta ao sincrono) — mas isso desliga TAMBEM o Redis pool; o DB pool nao tem toggle proprio (segue o gate dos hooks).

## 6. Sizing (pos-escala)

`SWOOLE_PG_POOL_SIZE` (env, ja existe). Com B2s(~100)/3 workers: 16 -> 48 + ~20 reserva = 68 < 100. Default conservador ate o load-test; subir conforme medicao.

## 7. Testes / criterio de sucesso (testing-first)

- **Unit (local):** factory monta Connection valida do PDO do pool; CoroutineDatabaseManager delega fora de coroutine; rollback no RequestTerminated com tx aberta.
- **Integracao no dev (swoole real, Postgres dev):** N coroutines concorrentes fazendo queries -> 0 colisao de protocolo; transacoes em coroutines distintas isoladas (commit/rollback nao vazam); 0 vazamento cross-tenant (nao regride Fase 2).
- **Load-test:** conc=25/50/100; timeouts -> ~0 enquanto `conc <= pool x workers`; comparar ANTES (conexao unica) vs DEPOIS (pool).
- **TLS Azure:** validar sob carga apontando pro Postgres Azure (apos escala), nao so o dev.
- **Gate de prod:** so ligar (ja esta on via hooks) apos escalar o Postgres + load-test verde. Testes locais nao entram no commit (regra 10).

## 8. Fora de escopo

- Demais conexoes (`pgsql_read`/`tenancy`/`legacy`/`webhook`/`carga`).
- §5/§6 do SWOOLE_PERFORMANCE (Atomic/Table) — fases proprias.
