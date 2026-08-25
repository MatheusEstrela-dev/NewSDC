# PDO Pool transparente por-coroutine (B1) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o Eloquent (`pgsql`) coroutine-safe sob `SWOOLE_HOOK_ALL` dando a cada coroutine sua propria `Connection` (PDO do `SwoolePdoPool`), eliminando colisao de protocolo e vazamento de transacao entre requests.

**Architecture:** Espelha o Redis pool da Fase 3. `CoroutineDatabaseManager` (estende `DatabaseManager`) resolve, sob hooks + em coroutine, uma `PostgresConnection` por-coroutine — PDO emprestado do `SwoolePdoPool`, embrulhado pela `ConnectionFactory` do framework (`db.factory`), guardada em `Coroutine::getContext()`, devolvida (rollback se tx aberta) no `RequestTerminated`. Fora de coroutine/hooks off → `DatabaseManager` padrao.

**Tech Stack:** Laravel 12 (DatabaseManager/ConnectionFactory/PostgresConnection), Swoole 6 (`Coroutine`, `Channel`, `getContext`), `App\Support\Database\SwoolePdoPool` (ja existe), Azure Postgres Flexible (TLS).

## Global Constraints

- Sem emojis no código (gitmoji só em commits). [regra 2]
- **Testes NÃO entram no commit** — TDD local, `git add` só de produção. [regra 10]
- Escopo: SOMENTE a conexão `pgsql`. `pgsql_read`/`tenancy`/`legacy`/`webhook`/`carga` fora.
- Só age sob hooks (`hooksEnabled()`, mesmo gate do Redis) + em coroutine; senão Laravel padrão.
- `pool_size × workers ≤ max_connections − reserva`. B1ms=50 (dev/atual); escalar p/ B2s(~100) antes do prod load.
- NÃO regredir o isolamento de tenant da Fase 2 (`TenantContext` por-coroutine).
- Reusa `SwoolePdoPool` existente (não cria pool novo).

---

## File Structure

- Modify `app/Support/Database/SwoolePdoPool.php` — adicionar `warm()` (pré-cria conexões no boot).
- Create `app/Support/Database/CoroutineDatabaseManager.php` — `DatabaseManager` coroutine-aware (`connection`, `releaseCurrentCoroutine`).
- Modify `app/Providers/OctaneServiceProvider.php` — `extend('db')` sob hooks; `warm()` o pool no WorkerStarting; `releaseCurrentCoroutine()` no RequestTerminated.
- Test (NÃO commitar): `tests/Unit/Support/Database/CoroutineDatabaseManagerTest.php` + scripts de validação no container dev.

---

### Task 1: `warm()` no SwoolePdoPool

**Files:**
- Modify: `app/Support/Database/SwoolePdoPool.php`

**Interfaces:**
- Produces: `SwoolePdoPool::warm(): void` — pré-cria conexões até `size`.

- [ ] **Step 1: Confirmar a estrutura atual do pool**

Run: `grep -nE 'private|function (acquire|run|fromConnection)' app/Support/Database/SwoolePdoPool.php`
Expected: ver `$dsn, $username, $password, $options, $size, $channel, $created` e `acquire()` criando `new PDO(...)`.

- [ ] **Step 2: Implementar `warm()`** (adicionar como método público, espelhando o `warm()` do Redis)

```php
    /**
     * Pre-cria as conexoes ate o teto (chamar no WorkerStarting). Move o custo
     * de abrir conexao/handshake TLS do burst do request para o boot do worker.
     * Falha de criacao interrompe sem derrubar (restantes sobem on-demand).
     */
    public function warm(): void
    {
        while ($this->created < $this->size) {
            try {
                $pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            } catch (\Throwable $e) {
                break;
            }
            $this->created++;
            $this->channel->push($pdo);
        }
    }
```

- [ ] **Step 3: Validar no container dev (swoole real, Postgres dev)**

Run (host):
```bash
docker exec -i newsdc_dev_app sh -c 'cat > /tmp/wp.php' <<'PHP'
<?php
require '/var/www/vendor/autoload.php'; $app=require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\Database\SwoolePdoPool;
\Swoole\Coroutine\run(function(){
  $p = SwoolePdoPool::fromConnection('pgsql', 4);
  $p->warm();
  echo "available=".$p->available()." (esperado 4)\n";  // se o pool nao tiver available(), usar reflection ou logar created
});
PHP
docker exec newsdc_dev_app php /tmp/wp.php 2>&1 | tail -3; docker exec newsdc_dev_app rm -f /tmp/wp.php
```
Expected: `available=4` (pool cheio). Se `available()` não existir no SwoolePdoPool, adicionar `public function available(): int { return $this->channel->length(); }` neste passo.

- [ ] **Step 4: Commit (SÓ produção)**

```bash
git add app/Support/Database/SwoolePdoPool.php
git commit -m "⚡ perf(swoole): warm() no SwoolePdoPool (pre-aquece no WorkerStarting)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: CoroutineDatabaseManager

**Files:**
- Create: `app/Support/Database/CoroutineDatabaseManager.php`
- Test (local): `tests/Unit/Support/Database/CoroutineDatabaseManagerTest.php`

**Interfaces:**
- Consumes: `SwoolePdoPool` (binding `swoole.pgsql.pool`), `db.factory` (ConnectionFactory do Laravel).
- Produces: `CoroutineDatabaseManager extends Illuminate\Database\DatabaseManager` com `connection($name=null)` por-coroutine e `releaseCurrentCoroutine(): void`.

- [ ] **Step 1: Write the failing test** (fallback fora de coroutine delega ao pai)

```php
<?php

namespace Tests\Unit\Support\Database;

use App\Support\Database\CoroutineDatabaseManager;
use Illuminate\Database\Connection;
use Tests\TestCase;

final class CoroutineDatabaseManagerTest extends TestCase
{
    public function test_fora_de_coroutine_delega_ao_databasemanager_padrao(): void
    {
        $mgr = new CoroutineDatabaseManager($this->app, $this->app['db.factory']);

        $conn = $mgr->connection('pgsql');

        $this->assertInstanceOf(Connection::class, $conn);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec newsdc_dev_app vendor/bin/phpunit tests/Unit/Support/Database/CoroutineDatabaseManagerTest.php` (se não houver phpunit no container, validar via script bootstrap como nas outras tasks)
Expected: FAIL — classe não encontrada. (Se o container for `--no-dev` sem phpunit, pular pro Step 3 e validar por script.)

- [ ] **Step 3: Write minimal implementation** (`app/Support/Database/CoroutineDatabaseManager.php`)

```php
<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Swoole\Coroutine;

/**
 * DatabaseManager coroutine-aware. Sob hooks Swoole + dentro de coroutine,
 * a conexao 'pgsql' e resolvida POR-COROUTINE: um objeto Connection proprio
 * (PDO emprestado do SwoolePdoPool, embrulhado pela ConnectionFactory do
 * framework), guardado em Coroutine::getContext(). Isso isola tanto o socket
 * PDO quanto o estado de transacao (Connection::$transactions) entre coroutines.
 * Fora de coroutine / hooks off / outra conexao -> DatabaseManager padrao.
 */
final class CoroutineDatabaseManager extends DatabaseManager
{
    private const CTX_KEY = '__sdc_pgsql_coroutine_connection';

    public function connection($name = null)
    {
        if ($this->shouldPool($name)) {
            $ctx = Coroutine::getContext();
            if (! isset($ctx[self::CTX_KEY])) {
                $ctx[self::CTX_KEY] = $this->makePooledPgsqlConnection();
            }

            return $ctx[self::CTX_KEY];
        }

        return parent::connection($name);
    }

    /** Devolve ao pool a conexao da coroutine atual (chamar no RequestTerminated). */
    public function releaseCurrentCoroutine(): void
    {
        if (! $this->inCoroutine()) {
            return;
        }

        $ctx = Coroutine::getContext();
        $conn = $ctx[self::CTX_KEY] ?? null;
        if (! $conn instanceof Connection) {
            return;
        }

        // Transacao aberta no fim do request -> rollback antes de devolver,
        // senao a proxima coroutine que pegar o PDO herda a transacao.
        try {
            while ($conn->transactionLevel() > 0) {
                $conn->rollBack();
            }
        } catch (\Throwable $e) {
        }

        $pool = $this->app->make('swoole.pgsql.pool');
        try {
            $pool->release($conn->getPdo());
        } catch (\Throwable $e) {
            $pool->discard();
        }

        unset($ctx[self::CTX_KEY]);
    }

    private function shouldPool($name): bool
    {
        $resolved = $name ?: $this->getDefaultConnection();

        return $resolved === 'pgsql'
            && $this->inCoroutine()
            && $this->app->bound('swoole.pgsql.pool');
    }

    private function inCoroutine(): bool
    {
        return extension_loaded('swoole')
            && class_exists(Coroutine::class)
            && Coroutine::getCid() > 0;
    }

    private function makePooledPgsqlConnection(): Connection
    {
        $pool = $this->app->make('swoole.pgsql.pool');
        $pdo = $pool->acquire();
        $config = $this->app['config']['database.connections.pgsql'];

        // ConnectionFactory do framework embrulha o PDO emprestado numa
        // PostgresConnection valida (grammar/processor/config corretos).
        return $this->app->make('db.factory')->createConnection(
            'pgsql',
            $pdo,
            $config['database'] ?? '',
            $config['prefix'] ?? '',
            $config
        );
    }
}
```

- [ ] **Step 4: Validar fallback no dev** (fora de coroutine → Connection padrão + query funciona)

Run:
```bash
docker exec -i newsdc_dev_app sh -c 'cat > /tmp/cdm.php' <<'PHP'
<?php
require '/var/www/vendor/autoload.php'; $app=require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\Database\CoroutineDatabaseManager;
$mgr=new CoroutineDatabaseManager($app,$app['db.factory']);
$c=$mgr->connection('pgsql');
echo "classe: ".get_class($c)."\n";
echo "select 1 => ".$c->selectOne('select 1 as x')->x."\n";
PHP
docker exec newsdc_dev_app php /tmp/cdm.php 2>&1 | tail -4; docker exec newsdc_dev_app rm -f /tmp/cdm.php
```
Expected: `classe: Illuminate\Database\PostgresConnection` + `select 1 => 1` (fora de coroutine delega ao pai, conecta no Postgres dev).

- [ ] **Step 5: Commit (SÓ produção)**

```bash
git add app/Support/Database/CoroutineDatabaseManager.php
git commit -m "✨ feat(swoole): CoroutineDatabaseManager (Connection pgsql por-coroutine)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Wiring no OctaneServiceProvider

**Files:**
- Modify: `app/Providers/OctaneServiceProvider.php`

**Interfaces:**
- Consumes: `CoroutineDatabaseManager` (Task 2), `SwoolePdoPool::warm()` (Task 1).
- Produces: `db` trocado por CoroutineDatabaseManager sob hooks; pool pgsql `warm()`-ado no WorkerStarting; `releaseCurrentCoroutine()` no RequestTerminated.

- [ ] **Step 1: Bind `db` sob hooks** — no `register()`, junto do bloco do Redis (`if ($this->hooksEnabled())`), adicionar:

```php
            $this->app->extend('db', function ($manager, $app) {
                return new \App\Support\Database\CoroutineDatabaseManager($app, $app['db.factory']);
            });
```

- [ ] **Step 2: `warm()` o pool pgsql** — em `bootSwoolePdoPool()`, após criar o singleton `swoole.pgsql.pool`, pré-aquecer sob hooks:

```php
            $this->app->singleton('swoole.pgsql.pool', fn () => SwoolePdoPool::fromConnection('pgsql', $size));
            if ($this->hooksEnabled()) {
                $this->app->make('swoole.pgsql.pool')->warm();
            }
```

- [ ] **Step 3: Devolver no RequestTerminated** — no listener `RequestTerminated`, após `releaseRedisCoroutine()`, adicionar:

```php
            $this->releasePgsqlCoroutine();
```

E o método (junto dos outros helpers):

```php
    protected function releasePgsqlCoroutine(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        try {
            $db = $this->app->make('db');
            if ($db instanceof \App\Support\Database\CoroutineDatabaseManager) {
                $db->releaseCurrentCoroutine();
            }
        } catch (\Throwable $e) {
        }
    }
```

- [ ] **Step 4: php -l + boot fora de hooks (sem regressão)**

Run:
```bash
docker exec newsdc_dev_app php -l /var/www/app/Providers/OctaneServiceProvider.php
docker exec newsdc_dev_app php artisan config:clear 2>&1 | tail -2
```
Expected: `No syntax errors detected` + config:clear sem erro (bind só age sob hooks; em boot normal, `db` é o padrão).

- [ ] **Step 5: Commit (SÓ produção)**

```bash
git add app/Providers/OctaneServiceProvider.php
git commit -m "✨ feat(swoole): wire PDO Pool por-coroutine no OctaneServiceProvider

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Validação de integração no dev (testing-first — o foco do "para teste")

**Files:** nenhum de produção; scripts locais no container dev.

**Interfaces:** Consome Tasks 1-3.

- [x] **Step 1: Concorrência + transação isolada + tenant (hooks on, Postgres dev)** — VALIDADO 2026-08-25

Resultado medido (Postgres dev, `hook_flags=SWOOLE_HOOK_ALL`, script CLI isolado — não passa pelo servidor Octane, então não multiplica por `OCTANE_WORKERS`):

| pool | conc | ok | collision | timeout | other |
|---|---|---|---|---|---|
| 16 | 24  | 24  | 0 | 0 | 0 |
| 16 | 50  | 50  | 0 | 0 | 0 |
| 16 | 100 | 100 | 0 | 0 | 0 |
| 24 | 100 | 100 | 0 | 0 | 0 |
| 1  | 100 | 100 | 0 | 0 | 0 |

`collision=0` e `other=0` em todas: sem colisão de protocolo e sem vazamento de nível de transação.

Prova de que o pool está de fato engajado (contada pelo próprio Postgres, `pg_stat_activity` antes/depois do `warm()`): `pool=4` abriu exatamente 4 conexões, `pool=16` abriu 16. E `db` resolve para `CoroutineDatabaseManager`, `hook_flags=2143283199`.

RESSALVA: as queries do harness são triviais (`select count(*) from pg_class`). Que `pool=1` sustente conc=100 NÃO generaliza para queries reais, que seguram a conexão por muito mais tempo. Não usar isto para justificar pool pequeno.

Run:
```bash
docker exec -i newsdc_dev_app sh -c 'cat > /tmp/dbload.php' <<'PHP'
<?php
putenv('OCTANE_SERVER=swoole'); $_ENV['OCTANE_SERVER']=$_SERVER['OCTANE_SERVER']='swoole';
putenv('OCTANE_HOOK_FLAGS_ENABLED=true'); $_ENV['OCTANE_HOOK_FLAGS_ENABLED']=$_SERVER['OCTANE_HOOK_FLAGS_ENABLED']='true';
require '/var/www/vendor/autoload.php'; $app=require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\Database\SwoolePdoPool; use Swoole\Coroutine\WaitGroup; use Illuminate\Support\Facades\DB;
$app->instance('swoole.pgsql.pool', SwoolePdoPool::fromConnection('pgsql', 16));
$app->make('swoole.pgsql.pool')->warm();
$s=['ok'=>0,'collision'=>0,'timeout'=>0,'other'=>0];
\Swoole\Coroutine\run(function() use(&$s){
  $wg=new WaitGroup();
  for($i=0;$i<24;$i++){$wg->add();\Swoole\Coroutine::create(function()use($i,$wg,&$s){
    try{
      for($j=0;$j<8;$j++){
        $n=DB::connection('pgsql')->selectOne('select count(*) c from pg_class')->c;
        // transacao isolada: abre, conta nivel, rollback -> nao deve vazar p/ outra coroutine
        DB::connection('pgsql')->beginTransaction();
        if(DB::connection('pgsql')->transactionLevel()!==1){throw new \RuntimeException("tx level vazou: ".DB::connection('pgsql')->transactionLevel());}
        DB::connection('pgsql')->rollBack();
      }
      $s['ok']++;
      $app=app(); $db=$app->make('db'); if($db instanceof \App\Support\Database\CoroutineDatabaseManager){$db->releaseCurrentCoroutine();}
    }catch(\Throwable $e){$m=$e->getMessage();
      if(stripos($m,'another coroutine')!==false||stripos($m,'protocol')!==false)$s['collision']++;
      elseif(stripos($m,'esgotado')!==false)$s['timeout']++;
      else{$s['other']++; echo "OTHER[$i]:".substr($m,0,140)."\n";}}
    finally{$wg->done();}});}
  $wg->wait();});
echo "conc=24x8 pool=16 -> ok={$s['ok']} collision={$s['collision']} timeout={$s['timeout']} other={$s['other']}\n";
PHP
docker exec newsdc_dev_app php /tmp/dbload.php 2>&1 | tail -6; docker exec newsdc_dev_app rm -f /tmp/dbload.php
```
Expected: `collision=0` (sem colisão de protocolo), `other=0`, transação isolada (sem "tx level vazou"). `timeout` pode ser >0 se conc(24) > pool(16) — esperado; rodar também com pool=24 → timeout=0. (Como o Redis: size ≥ concorrência → 0 timeout.)

- [ ] **Step 2: Registrar resultado no commit de doc (opcional)** — se quiser, anexar os números ao spec. Senão, seguir.

---

### Task 5: Gate de produção (escala + load-test Azure) — NÃO automático

**Files:** nenhum de produção; mudança de infra + env no App Service.

- [ ] **Step 1: Escalar o Postgres** (libera `max_connections`) — confirmar custo antes.

Run: `az postgres flexible-server update -g Defesa_Civil -n sdc-postgres --sku-name Standard_B2s --tier Burstable`
Expected: server volta `Ready` (após ~30-60s de failover); `max_connections` sobe (~100 no B2s). Verificar: `az postgres flexible-server parameter show -g Defesa_Civil -s sdc-postgres -n max_connections --query value -o tsv`.

- [ ] **Step 2: Definir o pool size** seguro

Run: `az webapp config appsettings set -n sdcdefesa -g Defesa_Civil --settings SWOOLE_PG_POOL_SIZE=16`
(fórmula: `16 × 3 workers = 48 + reserva 20 = 68 < 100`).

- [ ] **Step 3: Build + push (com warm/manager) + deploy por digest** (mesmo fluxo dos deploys anteriores: build retry, push retry, `config container set @digest`, stop/start). Hooks já estão ON.

- [ ] **Step 4: Load-test + checagem de conexões**

Run: 40+ concorrentes em `/` e `/login` (xargs -P 20), 2-3 rodadas, **+ monitorar** `pg_stat_activity` (conexões ≤ ~max_connections):
Expected: **0 5xx**, 0 `bound to another coroutine`/`protocol`, `pg_stat_activity` (datname=sdc) ≤ pool×workers+reserva, latência estável.

- [ ] **Step 5: Rollback se falhar** — `SWOOLE_PG_POOL_SIZE` menor ou (último caso) `OCTANE_HOOK_FLAGS_ENABLED=false`; reverter SKU se necessário.

---

## Self-Review

- **Cobertura do spec:** warm (Task 1) ✓ §3.1; CoroutineDatabaseManager + per-coroutine Connection + rollback no terminated (Task 2) ✓ §2/§3; wiring extend('db')+warm+release (Task 3) ✓ §3.1; isolamento de tenant + transação + load (Task 4/5) ✓ §7; escala Postgres + sizing (Task 5) ✓ §0/§6; fallback fora de coroutine (Task 2 Step 4) ✓ §2.
- **Placeholders:** nenhum; código completo. As verificações via script (container `--no-dev` sem phpunit) são instrução explícita, não placeholder.
- **Consistência de tipos:** `SwoolePdoPool::{warm,acquire,release,discard,available,fromConnection}`, `CoroutineDatabaseManager::{connection,releaseCurrentCoroutine}` usados igual entre Tasks 1→2→3.
- **Regra 10:** commits só de produção; testes/scripts ficam locais.
- **Risco:** ALTO (DB) — Tasks 1-4 validam no dev antes do gate de prod (Task 5).
