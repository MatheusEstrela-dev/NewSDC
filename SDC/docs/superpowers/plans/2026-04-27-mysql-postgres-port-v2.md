# MySQL -> PostgreSQL Port (NewSDC) v2 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar o NewSDC de MySQL para PostgreSQL 16 nativo, removendo todas as conexoes MySQL e aproveitando JSONB com GIN indexes.

**Architecture:** Script Artisan `db:port-postgres` faz substituicoes em massa nas migrations (json->jsonb+GIN, useCurrentOnUpdate). ENUMs nao precisam de conversao — o `PostgresGrammar` do Laravel ja os traduz para `varchar(255) CHECK(...)` nativamente. Excecoes complexas (raw SQL em 6 arquivos PHP, infra docker, AppServiceProvider) recebem edicao manual cirurgica.

**Tech Stack:** Laravel 9+, PostgreSQL 16-alpine, Docker Compose, PHP Artisan

**Spec de referencia:** `docs/superpowers/specs/2026-04-27-mysql-postgres-newSDC-v2-design.md`

---

## File Map

| Arquivo | Acao |
|---------|------|
| `docker/docker-compose.yml` | Substituir servico `db` (mysql:8.0 -> postgres:16-alpine), remover mysql-exporter |
| `docker/env.example` | DB_CONNECTION=pgsql, DB_PORT=5432 |
| `config/database.php` | default pgsql, remover blocos legacy/carga/tenancy |
| `app/Providers/AppServiceProvider.php` | Bloco NativePHP: mysql -> pgsql |
| `app/Console/Commands/PortToPostgres.php` | **Criar** — Artisan command de substituicao em massa |
| `database/migrations/*.php` (20 arquivos) | ->json() -> ->jsonb() + GIN index — via script |
| `database/migrations/*.php` (3 arquivos) | ->useCurrentOnUpdate() -> remover — via script |
| `app/Modules/Decretacoes/Services/ProcessoQueryService.php` | CAST(... AS UNSIGNED) -> BIGINT (linhas 713, 934) |
| `app/Modules/Decretacoes/Services/ProcessoExportBIService.php` | CAST(... AS UNSIGNED) -> BIGINT (linha 128) |
| `app/Modules/Decretacoes/Services/ProcessoExportService.php` | CAST(... AS UNSIGNED) -> BIGINT (linha 149) |
| `app/Http/Controllers/Api/HealthCheckController.php` | SHOW STATUS -> pg_stat_activity (linha 364) |
| `app/Modules/Pae/Services/PaeProtocoloService.php` | SUBSTRING_INDEX -> SPLIT_PART (linha 62) |
| `database/migrations/2026_02_10_000001_enhance_permission_system.php` | DATABASE() -> 'public' em information_schema (linhas 184, 210) |

---

## Task 1: Infra — Docker, Config, AppServiceProvider

**Files:**
- Modify: `docker/docker-compose.yml`
- Modify: `docker/env.example`
- Modify: `config/database.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Verificar uso das conexoes legadas**

```bash
grep -rn "connection('mysql')\|connection('legacy')\|connection('carga')\|connection('tenancy')" app/
```

Saida esperada: apenas `app/Providers/AppServiceProvider.php:74` — tratado no Step 4.
Se houver outros arquivos, portar para `pgsql` antes de continuar.

- [ ] **Step 2: Substituir servico `db` no docker/docker-compose.yml**

Localizar o bloco `db:` (linha ~137) e substituir o servico completo:

```yaml
  db:
    image: postgres:16-alpine
    container_name: newsdc_db
    hostname: db
    restart: unless-stopped
    labels:
      - "com.docker.compose.project=newsdc"
      - "com.docker.compose.service=db"

    secrets:
      - db_password

    environment:
      POSTGRES_DB: ${DB_DATABASE:-sdc}
      POSTGRES_USER: ${DB_USERNAME:-sdc}
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
      TZ: America/Sao_Paulo

    volumes:
      - newsdc_db_data:/var/lib/postgresql/data

    ports:
      - "5432:5432"

    networks:
      - sdc_network

    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-sdc} -d ${DB_DATABASE:-sdc}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
```

Tambem remover:
- Secret `db_root_password` de qualquer servico que a referencie e do bloco `secrets:` global
- Servico `mysql-exporter` (bloco inteiro a partir da linha ~650)
- Volume `newsdc_db_data:/var/lib/mysql` (substituido pelo novo volume acima)

Nos servicos que tem `DB_CONNECTION: mysql` (linhas ~74 e ~321), alterar:
```yaml
DB_CONNECTION: pgsql
DB_PORT: ${DB_PORT:-5432}
```

- [ ] **Step 3: Atualizar docker/env.example**

```
# ANTES
DB_CONNECTION=mysql
DB_PORT=3306

# DEPOIS
DB_CONNECTION=pgsql
DB_PORT=5432
```

- [ ] **Step 4: Atualizar config/database.php**

Linha 18 — alterar default:
```php
// ANTES
'default' => env('DB_CONNECTION', 'mysql'),

// DEPOIS
'default' => env('DB_CONNECTION', 'pgsql'),
```

Remover completamente os tres blocos de conexao (legacy ~linha 67, carga ~linha 88, tenancy ~linha 108).

- [ ] **Step 5: Corrigir AppServiceProvider.php — bloco NativePHP (~linha 43)**

```php
// ANTES (~linha 59-68)
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

// DEPOIS
config([
    'database.default' => 'pgsql',
    'database.connections.pgsql.host' => env('NATIVE_DB_HOST', '10.0.2.2'),
    'database.connections.pgsql.port' => env('NATIVE_DB_PORT', '5432'),
    'database.connections.pgsql.database' => env('DB_DATABASE', 'sdc'),
    'database.connections.pgsql.username' => env('DB_USERNAME', 'sdc'),
    'database.connections.pgsql.password' => env('DB_PASSWORD', 'secret'),
    'database.connections.pgsql.options' => [
        \PDO::ATTR_TIMEOUT => 3,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ],
]);
```

Linha ~74:
```php
// ANTES
\DB::connection('mysql')->getPdo();
\Log::info('NativePHP DB: MySQL connection SUCCESS');

// DEPOIS
\DB::connection('pgsql')->getPdo();
\Log::info('NativePHP DB: PostgreSQL connection SUCCESS');
```

Atualizar tambem qualquer `\Log::warning` que mencione "MySQL" para "PostgreSQL".

- [ ] **Step 6: Subir o container Postgres e verificar**

```bash
cd docker
docker compose down -v
docker compose up -d db
docker compose exec db pg_isready -U sdc -d sdc
```

Saida esperada: `db:5432 - accepting connections`

- [ ] **Step 7: Commit**

```bash
git add docker/docker-compose.yml docker/env.example config/database.php app/Providers/AppServiceProvider.php
git commit -m "feat: switch infrastructure from MySQL to PostgreSQL 16"
```

---

## Task 2: Criar Artisan Command `db:port-postgres`

**Files:**
- Create: `app/Console/Commands/PortToPostgres.php`

- [ ] **Step 1: Criar o arquivo do comando**

Escrever o conteudo completo em `app/Console/Commands/PortToPostgres.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class PortToPostgres extends Command
{
    protected $signature = 'db:port-postgres
        {--dry-run : Mostra mudancas sem escrever arquivos}
        {--batch= : Executa apenas uma categoria: json, timestamps}';

    protected $description = 'Porta migrations de MySQL para PostgreSQL (json->jsonb+GIN, useCurrentOnUpdate)';

    private bool $dryRun;
    private int $filesChanged = 0;
    private int $replacements = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $batch = $this->option('batch');

        if ($this->dryRun) {
            $this->warn('[DRY RUN] Nenhum arquivo sera escrito.');
            $this->newLine();
        }

        $migrations = glob(database_path('migrations/*.php'));

        foreach ($migrations as $path) {
            $original = file_get_contents($path);
            $content = $original;

            if (!$batch || $batch === 'json') {
                $content = $this->convertJson($content, $path);
            }

            if (!$batch || $batch === 'timestamps') {
                $content = $this->removeUseCurrentOnUpdate($content);
            }

            if ($content !== $original) {
                $this->filesChanged++;

                if ($this->dryRun) {
                    $this->showChangedLines($path, $original, $content);
                } else {
                    file_put_contents($path, $content);
                    $this->info('Atualizado: ' . basename($path));
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Arquivos alterados', $this->filesChanged],
                ['Substituicoes realizadas', $this->replacements],
            ]
        );

        if (!$this->dryRun) {
            $this->newLine();
            $this->verifyResults($batch);
        }

        return self::SUCCESS;
    }

    private function convertJson(string $content, string $path): string
    {
        if (!str_contains($content, '->json(')) {
            return $content;
        }

        // Rastreia o nome da tabela atual linha por linha
        // Suporta multiplos Schema::create/table() no mesmo arquivo
        $lines = explode("\n", $content);
        $result = [];
        $currentTable = pathinfo($path, PATHINFO_FILENAME);

        foreach ($lines as $line) {
            // Atualiza nome da tabela ao encontrar Schema::create/table
            if (preg_match("/Schema::(?:create|table)\('([^']+)'/", $line, $m)) {
                $currentTable = $m[1];
            }

            if (preg_match('/^(\s*)\$table->json\(\'([^\']+)\'\)/', $line, $m)) {
                $indent = $m[1];
                $colName = $m[2];
                $line = str_replace('->json(', '->jsonb(', $line);
                $result[] = $line;
                $idxName = 'idx_' . $currentTable . '_' . $colName;
                $result[] = $indent . '$table->index(\'' . $colName . '\', \'' . $idxName . '\', \'gin\');';
                $this->replacements++;
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    private function removeUseCurrentOnUpdate(string $content): string
    {
        if (!str_contains($content, '->useCurrentOnUpdate()')) {
            return $content;
        }

        $count = substr_count($content, '->useCurrentOnUpdate()');
        $this->replacements += $count;
        return str_replace('->useCurrentOnUpdate()', '', $content);
    }

    private function verifyResults(?string $batch): void
    {
        $this->info('=== Verificacao ===');
        $dir = database_path('migrations');

        if (!$batch || $batch === 'json') {
            $n = $this->grepCount('->json(', $dir);
            $label = $n === 0
                ? '<fg=green>OK (0 restantes)</>'
                : "<fg=red>FAIL ({$n} restantes)</>";
            $this->line("->json() restantes: {$label}");
        }

        if (!$batch || $batch === 'timestamps') {
            $n = $this->grepCount('useCurrentOnUpdate', $dir);
            $label = $n === 0
                ? '<fg=green>OK (0 restantes)</>'
                : "<fg=red>FAIL ({$n} restantes)</>";
            $this->line("useCurrentOnUpdate restantes: {$label}");
        }
    }

    private function grepCount(string $needle, string $dir): int
    {
        $count = 0;
        foreach (glob($dir . '/*.php') as $file) {
            $count += substr_count(file_get_contents($file), $needle);
        }
        return $count;
    }

    private function showChangedLines(string $path, string $original, string $new): void
    {
        $this->warn('--- ' . basename($path) . ' ---');
        $origLines = explode("\n", $original);
        $newLines  = explode("\n", $new);

        $max = max(count($origLines), count($newLines));
        for ($i = 0; $i < $max; $i++) {
            $o = $origLines[$i] ?? null;
            $n = $newLines[$i] ?? null;
            if ($o !== $n) {
                if ($o !== null) {
                    $this->line('<fg=red>- ' . OutputFormatter::escape($o) . '</>');
                }
                if ($n !== null) {
                    $this->line('<fg=green>+ ' . OutputFormatter::escape($n) . '</>');
                }
            }
        }
        $this->newLine();
    }
}
```

- [ ] **Step 2: Verificar que o comando e descoberto pelo Artisan**

```bash
docker compose exec app php artisan list | grep port
```

Saida esperada: `db:port-postgres  Porta migrations de MySQL para PostgreSQL`

Se nao aparecer, registrar manualmente em `app/Console/Kernel.php`:
```php
protected $commands = [
    \App\Console\Commands\PortToPostgres::class,
];
```

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/PortToPostgres.php
git commit -m "feat: add Artisan command db:port-postgres for mass migration porting"
```

---

## Task 3: Batch JSON -> JSONB + GIN Indexes

**Files:** `database/migrations/*.php` (20 arquivos com ->json())

- [ ] **Step 1: Rodar dry-run do batch JSON**

```bash
docker compose exec app php artisan db:port-postgres --dry-run --batch=json
```

Revisar o diff — confirmar que:
- `->json(` vira `->jsonb(`
- Linha de GIN index e injetada logo apos com o nome correto da tabela
- Nao ha falso-positivos (ex: comentarios com a palavra json)

- [ ] **Step 2: Aplicar batch JSON**

```bash
docker compose exec app php artisan db:port-postgres --batch=json
```

Saida esperada:
```
Atualizado: 2014_01_01_000000_create_orgaos_table.php
Atualizado: 2025_01_15_000001_create_tasks_table.php
[... outros 18 arquivos ...]

Arquivos alterados   | 20
Substituicoes        | 38+

->json() restantes: OK (0 restantes)
```

- [ ] **Step 3: Verificar zero restantes**

```bash
grep -rn "->json(" database/migrations/ | wc -l
```

Saida esperada: `0`

- [ ] **Step 4: Validar schema no Postgres**

```bash
docker compose exec app php artisan migrate:fresh
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS jsonb_count FROM information_schema.columns \
   WHERE table_schema = 'public' AND data_type = 'jsonb';"
```

Saida esperada: `jsonb_count >= 20`

```bash
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS gin_count FROM pg_indexes WHERE indexdef LIKE '%gin%';"
```

Saida esperada: `gin_count >= 20`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "refactor: convert all json columns to jsonb with GIN indexes for PostgreSQL"
```

---

## Task 4: Batch useCurrentOnUpdate

**Files:**
- `database/migrations/2026_02_12_130215_create_pae_ccpae_table.php`
- `database/migrations/2026_02_12_130429_create_pae_form_itens_table.php`
- `database/migrations/2026_02_12_131458_create_pae_tramit_prot_table.php`

- [ ] **Step 1: Aplicar batch timestamps**

```bash
docker compose exec app php artisan db:port-postgres --batch=timestamps
```

Saida esperada:
```
Atualizado: 2026_02_12_130215_create_pae_ccpae_table.php
Atualizado: 2026_02_12_130429_create_pae_form_itens_table.php
Atualizado: 2026_02_12_131458_create_pae_tramit_prot_table.php

Arquivos alterados   | 3
Substituicoes        | 4

useCurrentOnUpdate restantes: OK (0 restantes)
```

Nota: `create_pae_form_itens_table.php` tem 2 blocos `Schema::create` com useCurrentOnUpdate — o contador deve mostrar 4 substituicoes total.

- [ ] **Step 2: Verificar zero restantes**

```bash
grep -rn "useCurrentOnUpdate" database/migrations/ | wc -l
```

Saida esperada: `0`

- [ ] **Step 3: Executar migrate:fresh**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: todas as migrations rodam sem erro.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_02_12_130215_create_pae_ccpae_table.php
git add database/migrations/2026_02_12_130429_create_pae_form_itens_table.php
git add database/migrations/2026_02_12_131458_create_pae_tramit_prot_table.php
git commit -m "refactor: remove MySQL-only useCurrentOnUpdate from PAE migrations"
```

---

## Task 5: Raw SQL MySQL-only — 6 Arquivos

**Files:**
- Modify: `app/Modules/Decretacoes/Services/ProcessoQueryService.php`
- Modify: `app/Modules/Decretacoes/Services/ProcessoExportBIService.php`
- Modify: `app/Modules/Decretacoes/Services/ProcessoExportService.php`
- Modify: `app/Http/Controllers/Api/HealthCheckController.php`
- Modify: `app/Modules/Pae/Services/PaeProtocoloService.php`
- Modify: `database/migrations/2026_02_10_000001_enhance_permission_system.php`

- [ ] **Step 1: Corrigir ProcessoQueryService.php (2 locais)**

Localizar nas linhas 713 e 934:
```php
// ANTES
DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')

// DEPOIS (aplicar em ambas as linhas)
DB::raw('CAST(COALESCE(ed.valor, 0) AS BIGINT) as valor_numerico')
```

- [ ] **Step 2: Corrigir ProcessoExportBIService.php (linha 128)**

```php
// ANTES
DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')

// DEPOIS
DB::raw('CAST(COALESCE(ed.valor, 0) AS BIGINT) as valor_numerico')
```

- [ ] **Step 3: Corrigir ProcessoExportService.php (linha 149)**

```php
// ANTES
DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')

// DEPOIS
DB::raw('CAST(COALESCE(ed.valor, 0) AS BIGINT) as valor_numerico')
```

- [ ] **Step 4: Corrigir HealthCheckController.php (~linha 364)**

Ler o metodo `getDatabaseConnections()` e substituir o corpo do try:

```php
// ANTES
$result = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Max_used_connections', 'Threads_running')");

$connections = [];
foreach ($result as $row) {
    $connections[strtolower($row->Variable_name)] = (int) $row->Value;
}

return [
    'active'   => $connections['threads_connected'] ?? 0,
    'max_used' => $connections['max_used_connections'] ?? 0,
    'running'  => $connections['threads_running'] ?? 0,
];

// DEPOIS
$result = DB::selectOne("
    SELECT
        COUNT(*) FILTER (WHERE state IS NOT NULL) AS active,
        COUNT(*) FILTER (WHERE state = 'active')  AS running
    FROM pg_stat_activity
    WHERE datname = current_database()
");

return [
    'active'   => (int) ($result->active  ?? 0),
    'max_used' => 0,
    'running'  => (int) ($result->running ?? 0),
];
```

- [ ] **Step 5: Corrigir PaeProtocoloService.php (linha 62)**

Protocolo tem formato `dd.mm.YYYY.NNN` — posicao 4 no SPLIT_PART e o sequencial:

```php
// ANTES
"CAST(SUBSTRING_INDEX(num_protocolo, '.', -1) AS UNSIGNED)"

// DEPOIS
"CAST(SPLIT_PART(num_protocolo, '.', 4) AS BIGINT)"
```

- [ ] **Step 6: Corrigir enhance_permission_system.php (linhas 184 e 210)**

O metodo `addCheckConstraintIfNotExists` e `dropCheckConstraintIfExists` usam `DATABASE()` (funcao MySQL). Em PostgreSQL, `TABLE_SCHEMA` e o nome do schema ('public'), nao o banco.

```php
// ANTES (linha 184 e linha 210 — aplicar em ambos)
$constraints = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = '{$table}'
    AND CONSTRAINT_TYPE = 'CHECK'
");

// DEPOIS
$constraints = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = 'public'
    AND TABLE_NAME = '{$table}'
    AND CONSTRAINT_TYPE = 'CHECK'
");
```

- [ ] **Step 7: Verificar que nao ha mais SQL MySQL-only**

```bash
grep -rn "AS UNSIGNED\|SHOW STATUS\|SHOW TABLES\|SUBSTRING_INDEX\|GROUP_CONCAT\|DATE_FORMAT\|IFNULL\|DATABASE()" app/ database/migrations/
```

Saida esperada: zero linhas.

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Decretacoes/Services/ProcessoQueryService.php
git add app/Modules/Decretacoes/Services/ProcessoExportBIService.php
git add app/Modules/Decretacoes/Services/ProcessoExportService.php
git add app/Http/Controllers/Api/HealthCheckController.php
git add app/Modules/Pae/Services/PaeProtocoloService.php
git add database/migrations/2026_02_10_000001_enhance_permission_system.php
git commit -m "fix: replace MySQL-only SQL with PostgreSQL equivalents (BIGINT, pg_stat_activity, SPLIT_PART)"
```

---

## Task 6: Validacao Final

**Files:** nenhum arquivo editado — apenas validacao.

- [ ] **Step 1: migrate:fresh com seed completo**

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Saida esperada:
```
  Dropping all tables ..... DONE
  Running migrations ...... DONE
  Running seeders ......... DONE
```

Sem erros de FK, sem erros de tipo, sem erros de constraint.

- [ ] **Step 2: Verificar schema PostgreSQL**

```bash
# Zero ENUMs nativos (Laravel PostgresGrammar traduz enum para varchar CHECK, nao para pg ENUM)
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS enum_count FROM information_schema.columns \
   WHERE table_schema = 'public' AND udt_name = 'USER-DEFINED';"
# Esperado: 0

# 20+ colunas jsonb
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS jsonb_count FROM information_schema.columns \
   WHERE table_schema = 'public' AND data_type = 'jsonb';"
# Esperado: >= 20

# 20+ CHECK constraints (ENUMs nativos via Laravel + enhance_permission_system)
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS check_count FROM pg_constraint WHERE contype = 'c';"
# Esperado: >= 21

# 20+ GIN indexes
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) AS gin_count FROM pg_indexes WHERE indexdef LIKE '%gin%';"
# Esperado: >= 20
```

- [ ] **Step 3: Verificar driver e dados via Tinker**

```bash
docker compose exec app php artisan tinker --execute="
  echo 'Driver: ' . DB::connection()->getDriverName() . PHP_EOL;
  echo 'Users: '  . App\Models\User::count() . PHP_EOL;
"
```

Saida esperada:
```
Driver: pgsql
Users: [N > 0]
```

- [ ] **Step 4: Smoke test — rotas principais**

```bash
docker compose exec app php artisan route:list --columns=method,uri,name | grep -E "rat|pae|decretacao|tdap" | head -20
```

Saida esperada: lista de rotas sem erro de boot.

- [ ] **Step 5: Commit final**

```bash
git add docs/superpowers/plans/2026-04-27-mysql-postgres-port-v2.md
git commit -m "chore: PostgreSQL migration complete — migrations portadas, MySQL removido"
```

---

## Checklist de Conclusao

- [ ] `docker compose exec app php artisan migrate:fresh --seed` roda sem erro
- [ ] Driver reportado e `pgsql`
- [ ] Zero colunas `USER-DEFINED` no schema
- [ ] 20+ colunas `jsonb` no schema
- [ ] 20+ GIN indexes no schema
- [ ] Zero matches de `AS UNSIGNED|SHOW STATUS|SUBSTRING_INDEX|DATABASE()` em `app/` e migrations
- [ ] Zero matches de `->json(|useCurrentOnUpdate` em `database/migrations/`
- [ ] Login funciona na interface
- [ ] Modulos RAT, PAE, TDAP, Decretacoes carregam sem HTTP 500
