# SwoolePdoPool — Consumo B2 (helper Concurrency) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor o `SwoolePdoPool` já existente através de um helper `App\Support\Concurrency` que dá a hot paths conexões PDO por-coroutine (single query e queries paralelas), eliminando a contenção da conexão única sob Swoole, com fallback seguro fora de coroutine e dimensionamento de pool documentado para Azure e on-premise.

**Architecture:** B2 (helper explícito) — código opt-in. `Concurrency::run()` empresta um PDO do pool (`app('swoole.pgsql.pool')`) sob Swoole, ou usa o PDO do Eloquent fora de Swoole. `Concurrency::parallel()` roda N closures concorrentes via `Swoole\Coroutine\WaitGroup`, cada uma com seu PDO; degrada para sequencial fora de coroutine. NÃO mexe no resolver de conexão do Laravel (B1 fica para evolução futura).

**Tech Stack:** PHP 8.4, Laravel 12, Laravel Octane + Swoole 6.2, `Swoole\Coroutine\{Channel,WaitGroup}`, PostgreSQL 18, PHPUnit 11 (via phar no container dev).

**Regra do projeto (regra 10):** testes são escritos e rodados para TDD, mas **NÃO entram no commit**. Os passos de `git add`/commit deste plano incluem **apenas arquivos de produção**.

**Como rodar testes (container dev):**
`docker exec -e COLUMNS=120 newsdc_dev_app php /usr/local/bin/phpunit-phar <caminho-do-teste>`

---

### Task 1: `Concurrency::run()` — uma query com PDO do pool

**Files:**
- Create: `app/Support/Concurrency/Concurrency.php`
- Test (NÃO commitar): `tests/Unit/Support/Concurrency/ConcurrencyRunTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Concurrency;

use App\Support\Concurrency\Concurrency;
use Tests\TestCase;

class ConcurrencyRunTest extends TestCase
{
    public function test_run_executa_query_e_retorna_resultado(): void
    {
        // Fora de Swoole (phpunit CLI) o helper cai no fallback Eloquent.
        $valor = Concurrency::run(function (\PDO $pdo): int {
            $stmt = $pdo->query('SELECT 1 AS um');

            return (int) $stmt->fetchColumn();
        });

        $this->assertSame(1, $valor);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec -e COLUMNS=120 newsdc_dev_app php /usr/local/bin/phpunit-phar tests/Unit/Support/Concurrency/ConcurrencyRunTest.php`
Expected: FAIL — `Class "App\Support\Concurrency\Concurrency" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use Illuminate\Support\Facades\DB;
use PDO;
use Swoole\Coroutine;

/**
 * Helper opt-in para hot paths: dá uma conexão PDO por-coroutine a partir do
 * SwoolePdoPool sob Swoole, ou usa o PDO do Eloquent fora de coroutine.
 * NÃO altera o resolver de conexão do Laravel (B2).
 */
final class Concurrency
{
    /**
     * Roda a closure com um PDO emprestado do pool (Swoole) ou do Eloquent
     * (fallback). Retorna o que a closure retornar.
     *
     * @template T
     * @param  callable(PDO):T  $fn
     * @return T
     */
    public static function run(callable $fn): mixed
    {
        if (self::usaPool()) {
            /** @var \App\Support\Database\SwoolePdoPool $pool */
            $pool = app('swoole.pgsql.pool');

            return $pool->run($fn);
        }

        return $fn(DB::connection('pgsql')->getPdo());
    }

    /**
     * Verdadeiro quando há pool registrado E estamos dentro de uma coroutine
     * Swoole (getCid() >= 0). Fora disso, fallback síncrono.
     */
    private static function usaPool(): bool
    {
        return extension_loaded('swoole')
            && Coroutine::getCid() >= 0
            && app()->bound('swoole.pgsql.pool');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec -e COLUMNS=120 newsdc_dev_app php /usr/local/bin/phpunit-phar tests/Unit/Support/Concurrency/ConcurrencyRunTest.php`
Expected: PASS (1 test).

- [ ] **Step 5: Commit (apenas produção)**

```bash
git add app/Support/Concurrency/Concurrency.php
git commit -m "✨ feat(swoole): Concurrency::run() expõe SwoolePdoPool com fallback Eloquent"
```

---

### Task 2: `Concurrency::parallel()` — queries paralelas isoladas por coroutine

**Files:**
- Modify: `app/Support/Concurrency/Concurrency.php`
- Test (NÃO commitar): `tests/Unit/Support/Concurrency/ConcurrencyParallelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Concurrency;

use App\Support\Concurrency\Concurrency;
use Tests\TestCase;

class ConcurrencyParallelTest extends TestCase
{
    public function test_parallel_preserva_chaves_e_resultados_no_fallback(): void
    {
        // Fora de coroutine: execução sequencial, mas mesma API/contrato.
        $r = Concurrency::parallel([
            'a' => fn (\PDO $pdo) => (int) $pdo->query('SELECT 10')->fetchColumn(),
            'b' => fn (\PDO $pdo) => (int) $pdo->query('SELECT 20')->fetchColumn(),
        ]);

        $this->assertSame(['a' => 10, 'b' => 20], $r);
    }

    public function test_parallel_isola_conexao_por_coroutine(): void
    {
        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('swoole ausente');
        }

        $pids = [];

        \Swoole\Coroutine\run(function () use (&$pids): void {
            // Cada closure roda em sua coroutine; pg_backend_pid() distinto
            // comprova conexões diferentes (sem multiplexar uma só).
            $pids = Concurrency::parallel([
                'x' => fn (\PDO $pdo) => (int) $pdo->query('SELECT pg_backend_pid()')->fetchColumn(),
                'y' => fn (\PDO $pdo) => (int) $pdo->query('SELECT pg_backend_pid()')->fetchColumn(),
            ]);
        });

        $this->assertArrayHasKey('x', $pids);
        $this->assertArrayHasKey('y', $pids);
        $this->assertNotSame($pids['x'], $pids['y'], 'as duas coroutines usaram a MESMA conexão');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec -e COLUMNS=120 newsdc_dev_app php /usr/local/bin/phpunit-phar tests/Unit/Support/Concurrency/ConcurrencyParallelTest.php`
Expected: FAIL — `Call to undefined method App\Support\Concurrency\Concurrency::parallel()`.

> Nota: `test_parallel_isola_conexao_por_coroutine` só prova isolamento real se o pool estiver registrado no contexto do teste. Em phpunit puro o pool não é criado (só no `WorkerStarting` do Octane), então sob Swoole-CLI ele cai no fallback Eloquent (1 conexão) e os PIDs podem coincidir — nesse caso o teste vira `markTestSkipped`. Ver Step 3 para o guard.

- [ ] **Step 3: Write minimal implementation (adiciona `parallel` + skip-guard)**

Adicionar o método `parallel()` à classe `Concurrency` (após `run()`):

```php
    /**
     * Roda N closures concorrentemente (cada uma com seu PDO) sob coroutine
     * Swoole; degrada para sequencial fora de coroutine. Resultados retornam
     * nas MESMAS chaves do array de entrada.
     *
     * @param  array<array-key, callable(PDO):mixed>  $closures
     * @return array<array-key, mixed>
     */
    public static function parallel(array $closures): array
    {
        if (! self::usaPool()) {
            $out = [];
            foreach ($closures as $chave => $fn) {
                $out[$chave] = self::run($fn);
            }

            return $out;
        }

        $wg = new \Swoole\Coroutine\WaitGroup();
        $out = [];

        foreach ($closures as $chave => $fn) {
            $wg->add();
            Coroutine::create(function () use ($chave, $fn, &$out, $wg): void {
                try {
                    $out[$chave] = self::run($fn);
                } finally {
                    $wg->done();
                }
            });
        }

        $wg->wait();

        return $out;
    }
```

E no teste, trocar o corpo de `test_parallel_isola_conexao_por_coroutine` para pular quando não há pool real (fallback de 1 conexão):

```php
        if (! app()->bound('swoole.pgsql.pool')) {
            $this->markTestSkipped('pool não registrado fora do worker Octane; isolamento coberto pelo load-test (Task 4)');
        }
```
(inserir logo após o guard de `extension_loaded`).

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec -e COLUMNS=120 newsdc_dev_app php /usr/local/bin/phpunit-phar tests/Unit/Support/Concurrency/ConcurrencyParallelTest.php`
Expected: PASS — `test_parallel_preserva_chaves...` passa; `test_parallel_isola...` passa ou é `skipped` (sem pool no CLI). O isolamento real é provado no load-test da Task 4.

- [ ] **Step 5: Commit (apenas produção)**

```bash
git add app/Support/Concurrency/Concurrency.php
git commit -m "✨ feat(swoole): Concurrency::parallel() roda queries em coroutines isoladas"
```

---

### Task 3: Dimensionamento do pool (Azure/on-premise) + documentação

**Files:**
- Modify: `config/database.php:129-149` (bloco `pgsql` — adicionar comentário com a fórmula)
- Modify: `.env.example` (adicionar `SWOOLE_PG_POOL_SIZE` com nota)

- [ ] **Step 1: Documentar a fórmula no `config/database.php`**

Adicionar, logo acima do array `'options'` do bloco `'pgsql'` (linha ~145), o comentário:

```php
            // SwoolePdoPool (App\Support\Database\SwoolePdoPool) usa esta conexão.
            // TETO DE CONEXÕES (restrição dura, sobretudo on-premise):
            //   SWOOLE_PG_POOL_SIZE × OCTANE_WORKERS × instâncias  <=  max_connections − reserva
            // Azure: max_connections alto → default 16 ok.
            // On-premise modesto: medir max_connections do servidor e reduzir
            //   SWOOLE_PG_POOL_SIZE (ex.: 8) para não esgotar o Postgres.
```

- [ ] **Step 2: Adicionar a env no `.env.example`**

Acrescentar ao `.env.example` (seção de banco):

```dotenv
# Tamanho do pool de conexões PDO por worker sob Swoole (App\Support\Database\SwoolePdoPool).
# Teto: SWOOLE_PG_POOL_SIZE * OCTANE_WORKERS * instancias <= max_connections do Postgres - reserva.
# Azure: 16. On-premise modesto: reduzir (ex.: 8).
SWOOLE_PG_POOL_SIZE=16
```

- [ ] **Step 3: Verificar que o config carrega sem erro**

Run: `docker exec newsdc_dev_app php /var/www/artisan config:cache`
Expected: `Configuration cached successfully.` (sem erro de sintaxe). Depois rode `docker exec newsdc_dev_app php /var/www/artisan config:clear` para não fixar cache de dev.

- [ ] **Step 4: Commit (apenas produção)**

```bash
git add config/database.php .env.example
git commit -m "📝 docs(swoole): fórmula de dimensionamento do SwoolePdoPool (Azure/on-premise)"
```

---

### Task 4: Validação por load-test (Azure E on-premise) — harness efêmero

**Files:**
- Create (efêmero, NÃO commitar; em diretório montado e gitignored): `storage/app/_pool_loadtest.php`

Objetivo: provar empiricamente que um endpoint que usa `Concurrency::parallel()` sob Swoole **não** sofre a contenção da conexão única (timeouts → ~0 enquanto `conc <= pool_size`), e que conexões são distintas por coroutine.

- [ ] **Step 1: Escrever o harness de load-test (efêmero)**

```php
<?php
// EFEMERO — remover apos o teste (regra 10). Dispara N coroutines concorrentes
// usando Concurrency::parallel via uma closure que mede pg_backend_pid.
use App\Support\Concurrency\Concurrency;
use Swoole\Coroutine\WaitGroup;
use function Swoole\Coroutine\run;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = (int) ($argv[1] ?? 200);
$conc  = (int) ($argv[2] ?? 50);

run(function () use ($total, $conc): void {
    $wg = new WaitGroup();
    $sem = new \Swoole\Coroutine\Channel($conc);
    $erros = 0; $ok = 0; $pids = [];
    for ($i = 0; $i < $total; $i++) {
        $sem->push(1); $wg->add();
        \Swoole\Coroutine::create(function () use (&$erros, &$ok, &$pids, $wg, $sem): void {
            try {
                $r = Concurrency::parallel([
                    'a' => fn (\PDO $p) => (int) $p->query('SELECT pg_backend_pid()')->fetchColumn(),
                    'b' => fn (\PDO $p) => (int) $p->query('SELECT pg_sleep(0.01), pg_backend_pid()')->fetchColumn(2),
                ]);
                $pids[$r['a']] = true; $ok++;
            } catch (\Throwable $e) { $erros++; }
            finally { $sem->pop(); $wg->done(); }
        });
    }
    $wg->wait();
    echo "ok={$ok} erros={$erros} conexoes_distintas=".count($pids).PHP_EOL;
});
```

- [ ] **Step 2: Rodar contra o alvo local/on-premise (dev container)**

Run: `docker exec newsdc_dev_app php /var/www/storage/app/_pool_loadtest.php 200 50`
Expected: `erros=0` e `conexoes_distintas > 1` (o pool entregou múltiplas conexões; sem contenção). Comparar com `conc` acima de `SWOOLE_PG_POOL_SIZE` para observar o bloqueio cooperativo (espera, não erro).

- [ ] **Step 3: Rodar contra Azure staging (TLS)**

Apontar o container/app para o Postgres da Azure (`DB_HOST`, `DB_SSLMODE=require`) e repetir o Step 2. Expected: `erros=0` — confirma o ponto não validado da `SwoolePdoPool` (TLS Azure sob coroutine). Se houver erro de TLS/handshake, registrar e tratar no DSN do pool antes de prosseguir.

- [ ] **Step 4: Remover o harness efêmero**

```bash
# host
rm storage/app/_pool_loadtest.php
# container (se necessario)
docker exec newsdc_dev_app rm -f /var/www/storage/app/_pool_loadtest.php
```

- [ ] **Step 5: Sem commit** (nada de produção nesta task; resultados vão no PR como evidência no corpo).

---

## Follow-up (fora do escopo deste plano)

- **Adoção em hot paths:** migrar 2–3 repositórios de leitura pesada (relatórios/dashboards/BI) para `Concurrency::parallel()` — cada um é uma task pequena após o helper existir. Requer a §7.3 do spec (definir os alvos).
- **B1 (transparente no Eloquent):** só se a medição mostrar que o Eloquent geral ainda limita o RPS-alvo.
- **`pgsql_read`/outras conexões:** avaliar pool dedicado se virarem hot.

## Self-Review

- **Cobertura do spec:** §2 (pool existe, falta consumo) → Tasks 1–2 (helper). §3.1 (Azure/on-prem, TLS configurável, teto de conexões) → Task 3 + Task 4 Step 3. §5 (B2 escolhido, sem mexer no resolver) → Tasks 1–2. §6 (plano de teste, reusar harness, métrica de capacidade/conexões) → Task 4. §7.1 (sizing) → Task 3. §7.3/§7 B1 → Follow-up. ✔
- **Placeholders:** nenhum TBD/TODO; todo passo tem código/comando reais. ✔
- **Consistência de tipos:** `Concurrency::run(callable):mixed` e `Concurrency::parallel(array):array` usados igualmente nas Tasks 1, 2 e 4; `usaPool()` privado usado por ambos. ✔
- **Regra 10:** nenhum passo faz `git add` de `tests/` ou do harness. ✔
