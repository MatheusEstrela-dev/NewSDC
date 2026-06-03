# MySQL → PostgreSQL Port (NewSDC) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar o NewSDC de MySQL para PostgreSQL nativo, aproveitando JSONB, GIN indexes e CHECK constraints.

**Architecture:** As 130 migrations existentes sao refatoradas in-place — sem reescrita de logica de negocio. A camada de infra (docker, config) e trocada primeiro; depois as migrations sao corrigidas em batches por modulo; ao final, uma validacao completa com `migrate:fresh`.

**Tech Stack:** Laravel 9+, PostgreSQL 16-alpine, Docker Compose, `php artisan migrate:fresh`

---

## File Map

| Arquivo | Acao |
|---------|------|
| `docker/docker-compose.yml` | Substituir servico `db` (mysql:8.0 → postgres:16-alpine) |
| `docker/env.example` | DB_CONNECTION, DB_PORT |
| `config/database.php` | Default: mysql → pgsql |
| `database/migrations/2014_10_12_000000_create_users_table.php` | 2 ENUMs |
| `database/migrations/2026_04_14_000001_create_user_notification_preferences_table.php` | 1+ ENUMs |
| `database/migrations/2025_01_15_000001_create_tasks_table.php` | 4 ENUMs, 1 JSON |
| `database/migrations/2025_01_15_000002_create_task_comments_table.php` | 1 ENUM, 1 JSON |
| `database/migrations/2025_01_15_000004_create_task_approvals_table.php` | 1 ENUM |
| `database/migrations/2025_01_15_000005_create_task_sla_definitions_table.php` | 1 ENUM, 3 JSON |
| `database/migrations/2025_01_15_000007_create_task_audit_logs_table.php` | 1 ENUM, 1 JSON |
| `database/migrations/2025_11_27_000001_create_webhook_logs_table.php` | 1 JSON |
| `database/migrations/2025_11_27_000002_create_integrations_table.php` | 2 JSON |
| `database/migrations/2025_12_23_000001_create_permission_audit_log_table.php` | 1 JSON |
| `database/migrations/2025_12_27_184510_create_orgaos_table.php` | 1 ENUM, 1 JSON |
| `database/migrations/2025_12_28_000001_create_rats_table.php` | 2 JSON |
| `database/migrations/2025_12_28_120100_create_abrigos_table.php` | 1 JSON |
| `database/migrations/2025_12_28_140000_create_treinamentos_table.php` | 1 ENUM |
| `database/migrations/2025_12_28_140200_create_inscricoes_table.php` | 1 ENUM |
| `database/migrations/2025_12_28_140300_create_frequencias_table.php` | 1 ENUM |
| `database/migrations/2025_12_27_184613_create_processo_logs_table.php` | 1 JSON |
| `database/migrations/2025_12_29_000002_create_orgao_user_table.php` | 1 ENUM |
| `database/migrations/2026_01_28_000000_create_support_tickets_table.php` | 1 ENUM |
| `database/migrations/2026_02_02_000001_create_ai_tables.php` | 1 ENUM, 1 JSON |
| `database/migrations/2026_02_10_100002_create_rat_relato_recursos_table.php` | 2 ENUMs |
| `database/migrations/2026_02_10_100003_create_rat_recursos_empregados_table.php` | 1 ENUM |
| `database/migrations/2026_02_11_000002_create_audit_logs_table.php` | 1 ENUM, 1 JSON |
| `database/migrations/2026_02_12_130215_create_pae_ccpae_table.php` | useCurrentOnUpdate |
| `database/migrations/2026_02_12_130429_create_pae_form_itens_table.php` | useCurrentOnUpdate |
| `database/migrations/2026_02_12_131458_create_pae_tramit_prot_table.php` | useCurrentOnUpdate |
| `database/migrations/2026_02_12_130002_create_pae_form_templates_table.php` | 1 JSON |
| `database/migrations/2026_02_19_000001_create_planos_contingencia_table.php` | 1 ENUM |
| `database/migrations/2026_02_24_000001_create_webhook_events_table.php` | 1 ENUM, 1 JSON |
| `database/migrations/2026_03_02_164838_create_dec_entrada_processo_logs_table.php` | 1 JSON |
| `database/migrations/2026_03_09_000001_add_subentities_to_rats_table.php` | 1 JSON |
| `database/migrations/2026_03_09_000002_add_anexos_to_rats_table.php` | 1 JSON |
| `database/migrations/2026_03_10_000002_recreate_rat_polymorphic_tables.php` | 1 ENUM |
| `database/migrations/2026_03_11_000001_create_tenants_table.php` | 1 JSON |
| `database/migrations/2026_03_11_000003_create_rat_tipos_table.php` | 1 ENUM |
| `database/migrations/2026_03_11_000004_create_rat_ocorrencia_historico_table.php` | 1 JSON |

---

## Padrao de Refatoracao — Referencia Rapida

### ENUM → checkIn()
```php
// ANTES
$table->enum('status', ['ativo', 'inativo'])->default('ativo');

// DEPOIS
$table->string('status', 20)->default('ativo')
      ->checkIn(['ativo', 'inativo']);
```

### JSON → JSONB + GIN index
```php
// ANTES
$table->json('dados')->nullable();

// DEPOIS
$table->jsonb('dados')->nullable();
$table->index('dados', 'idx_' . $table->getTable() . '_dados', 'gin');
// Ou no método separado:
// $table->rawIndex("dados gin_trgm_ops", "idx_dados_gin");
// Simplificado (Laravel native):
$table->jsonb('dados')->nullable();
// Adicionar index GIN somente em colunas que tenhamos queries @> ou ?
```

**Nota sobre GIN index:** Adicionar GIN index apenas onde ha queries reais sobre o campo JSON (`whereJsonContains`, `@>`, `?`). Para colunas JSON puramente de armazenamento (sem filtro), `jsonb` sem index e suficiente.

### useCurrentOnUpdate → remover
```php
// ANTES
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

// DEPOIS
$table->timestamp('updated_at')->useCurrent();
// Eloquent gerencia updated_at via Model::save()
```

---

## Task 1: Infra — Docker e Config

**Files:**
- Modify: `docker/docker-compose.yml` (linhas 110–151: servico `db`)
- Modify: `config/database.php` (linha 18: default)
- Modify: `docker/env.example` (DB_CONNECTION, DB_PORT)

- [ ] **Step 1: Trocar servico `db` no docker-compose.yml**

Localizar o bloco `db:` (linhas ~110–151) e substituir:

```yaml
  # ==========================================================================
  # PostgreSQL 16
  # ==========================================================================
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
      test: [ "CMD-SHELL", "pg_isready -U ${DB_USERNAME:-sdc} -d ${DB_DATABASE:-sdc}" ]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
```

Tambem remover da lista de `secrets:` o `db_root_password` (nao existe no Postgres).

Atualizar o nome do volume no bloco `volumes:` ao final do arquivo:
```yaml
volumes:
  newsdc_db_data:  # era newsdc_db_data (mesmo nome, manter)
```

- [ ] **Step 2: Atualizar variaveis de ambiente no docker-compose.yml**

Nos servicos que tem `DB_CONNECTION: mysql` (linhas ~69 e ~297), alterar para:
```yaml
DB_CONNECTION: pgsql
DB_PORT: ${DB_PORT:-5432}
```

- [ ] **Step 3: Atualizar config/database.php**

Linha 18 — alterar default:
```php
// ANTES
'default' => env('DB_CONNECTION', 'mysql'),

// DEPOIS
'default' => env('DB_CONNECTION', 'pgsql'),
```

Conexoes `legacy`, `carga` e `tenancy` usam MySQL e sao para integracao com SDC legado. Manter como estao (driver mysql) — elas so sao usadas quando explicitamente solicitadas via `DB::connection('legacy')`.

- [ ] **Step 4: Atualizar docker/env.example**

```
# ANTES
DB_CONNECTION=mysql
DB_PORT=3306

# DEPOIS
DB_CONNECTION=pgsql
DB_PORT=5432
```

- [ ] **Step 5: Subir o container e verificar**

```bash
cd docker
docker compose down -v
docker compose up -d db
docker compose exec db pg_isready -U sdc -d sdc
```

Saida esperada: `db:5432 - accepting connections`

- [ ] **Step 6: Commit**

```bash
git add docker/docker-compose.yml config/database.php docker/env.example
git commit -m "feat: switch database engine from MySQL to PostgreSQL 16"
```

---

## Task 2: ENUMs — Tabela Users (Core)

**Files:**
- Modify: `database/migrations/2014_10_12_000000_create_users_table.php`
- Modify: `database/migrations/2026_04_14_000001_create_user_notification_preferences_table.php`

- [ ] **Step 1: Verificar que migrate:fresh FALHA sem as alteracoes**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: migrations rodam mas ENUMs viram varchar sem CHECK (validar com `\d users` no psql que nao ha constraint).

```bash
docker compose exec db psql -U sdc -d sdc -c "\d users"
```

Confirmar que coluna `status` e `character varying` sem CHECK constraint.

- [ ] **Step 2: Corrigir create_users_table.php**

Localizar e substituir os dois ENUMs:

```php
// ANTES (linha ~37)
$table->enum('status', [
    'active',
    'inactive',
    'suspended',
    'pending',
    'blocked'
])->default('pending');

// DEPOIS
$table->string('status', 20)->default('pending')
      ->checkIn(['active', 'inactive', 'suspended', 'pending', 'blocked']);
```

```php
// ANTES (linha ~65)
$table->enum('notification_update_mode', ['polling', 'realtime'])
    ->default('polling');

// DEPOIS
$table->string('notification_update_mode', 20)->default('polling')
      ->checkIn(['polling', 'realtime']);
```

Tambem remover o bloco MySQL-only de FOREIGN_KEY_CHECKS, que ja tem guard correto mas e desnecessario:
```php
// REMOVER os dois blocos (o guard já está certo, mas limpar é melhor)
if (DB::getDriverName() === 'mysql') {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
}
// ...
if (DB::getDriverName() === 'mysql') {
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}
```

- [ ] **Step 3: Corrigir create_user_notification_preferences_table.php**

Abrir o arquivo e aplicar o mesmo padrao para cada `->enum()` encontrado:
```php
// Padrao: ->enum('coluna', [...]) → ->string('coluna', N)->checkIn([...])
// N = comprimento do valor mais longo + margem (ex: se maior valor tem 10 chars, usar 20)
```

- [ ] **Step 4: Executar migrate:fresh e verificar CHECK constraint**

```bash
docker compose exec app php artisan migrate:fresh
docker compose exec db psql -U sdc -d sdc -c "\d users"
```

Saida esperada: coluna `status` tem `CHECK ((status)::text = ANY (...))` na descricao da tabela.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2014_10_12_000000_create_users_table.php
git add database/migrations/2026_04_14_000001_create_user_notification_preferences_table.php
git commit -m "refactor: convert users ENUMs to string+checkIn for PostgreSQL"
```

---

## Task 3: ENUMs — Modulo Tasks (5 migrations)

**Files:**
- Modify: `database/migrations/2025_01_15_000001_create_tasks_table.php`
- Modify: `database/migrations/2025_01_15_000002_create_task_comments_table.php`
- Modify: `database/migrations/2025_01_15_000004_create_task_approvals_table.php`
- Modify: `database/migrations/2025_01_15_000005_create_task_sla_definitions_table.php`
- Modify: `database/migrations/2025_01_15_000007_create_task_audit_logs_table.php`

- [ ] **Step 1: Corrigir create_tasks_table.php (4 ENUMs)**

```php
// ANTES
$table->enum('tipo', ['incidente', 'solicitacao', 'mudanca', 'problema'])
    ->index()
    ->comment('Tipo de task (Table Inheritance discriminator)');

// DEPOIS
$table->string('tipo', 20)->default('incidente')->index()
      ->comment('Tipo de task (Table Inheritance discriminator)')
      ->checkIn(['incidente', 'solicitacao', 'mudanca', 'problema']);
```

```php
// ANTES
$table->enum('status', [
    'aberta', 'em_analise', 'em_progresso',
    'aguardando_terceiros', 'resolvida', 'fechada', 'cancelada',
])->default('aberta')->index();

// DEPOIS
$table->string('status', 30)->default('aberta')->index()
      ->checkIn(['aberta', 'em_analise', 'em_progresso',
                 'aguardando_terceiros', 'resolvida', 'fechada', 'cancelada']);
```

```php
// ANTES
$table->enum('impacto', ['alto', 'medio', 'baixo'])->default('medio');

// DEPOIS
$table->string('impacto', 10)->default('medio')
      ->checkIn(['alto', 'medio', 'baixo']);
```

```php
// ANTES
$table->enum('urgencia', ['alta', 'media', 'baixa'])->default('media');

// DEPOIS
$table->string('urgencia', 10)->default('media')
      ->checkIn(['alta', 'media', 'baixa']);
```

- [ ] **Step 2: Corrigir create_task_comments_table.php (1 ENUM)**

Abrir o arquivo, localizar `->enum('tipo', ['comentario', 'atualizacao', 'sistema'])` e aplicar:
```php
$table->string('tipo', 20)->default('comentario')
      ->checkIn(['comentario', 'atualizacao', 'sistema']);
```

- [ ] **Step 3: Corrigir create_task_approvals_table.php (1 ENUM)**

Localizar `->enum('status', ['pendente', 'aprovado', 'rejeitado', 'cancelado'])` e aplicar:
```php
$table->string('status', 20)->default('pendente')
      ->checkIn(['pendente', 'aprovado', 'rejeitado', 'cancelado']);
```

- [ ] **Step 4: Corrigir create_task_sla_definitions_table.php (1 ENUM)**

Localizar `->enum('tipo_task', ['incidente', 'solicitacao', 'mudanca', 'problema'])` e aplicar:
```php
$table->string('tipo_task', 20)
      ->checkIn(['incidente', 'solicitacao', 'mudanca', 'problema']);
```

- [ ] **Step 5: Corrigir create_task_audit_logs_table.php (1 ENUM)**

Localizar `->enum('acao', [...])` e aplicar padrao checkIn com os valores existentes.

- [ ] **Step 6: Executar migrate:fresh**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: `Nothing to migrate.` nao deve aparecer — todas as 130 migrations devem rodar sem erro.

Verificar constraints no Postgres:
```bash
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT conname, consrc FROM pg_constraint WHERE contype = 'c' AND conrelid = 'tasks'::regclass;"
```

Saida esperada: 4 linhas com CHECK constraints para tipo, status, impacto, urgencia.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2025_01_15_000001_create_tasks_table.php
git add database/migrations/2025_01_15_000002_create_task_comments_table.php
git add database/migrations/2025_01_15_000004_create_task_approvals_table.php
git add database/migrations/2025_01_15_000005_create_task_sla_definitions_table.php
git add database/migrations/2025_01_15_000007_create_task_audit_logs_table.php
git commit -m "refactor: convert tasks module ENUMs to string+checkIn for PostgreSQL"
```

---

## Task 4: ENUMs — Modulos RAT, Webhook e Outros (14 migrations)

**Files (aplicar padrao ENUM → checkIn em cada um):**
- `database/migrations/2025_12_27_184510_create_orgaos_table.php`
- `database/migrations/2025_12_28_140000_create_treinamentos_table.php`
- `database/migrations/2025_12_28_140200_create_inscricoes_table.php`
- `database/migrations/2025_12_28_140300_create_frequencias_table.php`
- `database/migrations/2025_12_29_000002_create_orgao_user_table.php`
- `database/migrations/2026_01_28_000000_create_support_tickets_table.php`
- `database/migrations/2026_02_02_000001_create_ai_tables.php`
- `database/migrations/2026_02_10_100002_create_rat_relato_recursos_table.php`
- `database/migrations/2026_02_10_100003_create_rat_recursos_empregados_table.php`
- `database/migrations/2026_02_11_000002_create_audit_logs_table.php`
- `database/migrations/2026_02_19_000001_create_planos_contingencia_table.php`
- `database/migrations/2026_02_24_000001_create_webhook_events_table.php`
- `database/migrations/2026_03_10_000002_recreate_rat_polymorphic_tables.php`
- `database/migrations/2026_03_11_000003_create_rat_tipos_table.php`

- [ ] **Step 1: Abrir cada arquivo e aplicar padrao de substituicao**

Para cada arquivo, usar busca por `->enum(` e substituir pelo padrao:
```php
// PADRAO UNIVERSAL
// ANTES:
$table->enum('COLUNA', ['VAL1', 'VAL2', ...])->default('DEFAULT');

// DEPOIS:
$table->string('COLUNA', N)->default('DEFAULT')  // N = len(valor_mais_longo) + 10
      ->checkIn(['VAL1', 'VAL2', ...]);
```

Detalhes especificos por arquivo:

**rat_relato_recursos** (2 ENUMs):
```php
// ENUM 1: recurso_tipo
$table->string('recurso_tipo', 20)
      ->checkIn(['viatura', 'pe', 'aereo', 'aquatico', 'outro']);

// ENUM 2: viatura_condicao
$table->string('viatura_condicao', 20)->nullable()
      ->checkIn(['otima', 'boa', 'regular', 'ruim', 'pessima']);
// (verificar valores reais no arquivo antes de aplicar)
```

**rat_recursos_empregados** (1 ENUM):
```php
$table->string('recurso_tipo', 20)
      ->checkIn(['viatura', 'pe', 'aereo', 'aquatico', 'outro']);
```

**audit_logs** (1 ENUM — geralmente 'created','updated','deleted','restored'):
```php
$table->string('acao', 20)
      ->checkIn(['created', 'updated', 'deleted', 'restored']);
```

Para os demais arquivos (orgaos, treinamentos, inscricoes, frequencias, orgao_user, support_tickets, ai_tables, planos_contingencia, webhook_events, rat_polymorphic, rat_tipos): abrir cada um, identificar os valores do ENUM e aplicar o mesmo padrao.

- [ ] **Step 2: Executar migrate:fresh**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: todas as 130 migrations correm sem erro.

Verificar que nao ha mais coluna `enum` no schema:
```bash
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT table_name, column_name, udt_name FROM information_schema.columns
   WHERE table_schema = 'public' AND udt_name = 'USER-DEFINED'
   ORDER BY table_name, column_name;"
```

Saida esperada: 0 linhas (nenhuma coluna do tipo USER-DEFINED, que e como Postgres enumera ENUMs nativos).

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2025_12_27_184510_create_orgaos_table.php
git add database/migrations/2025_12_28_140000_create_treinamentos_table.php
git add database/migrations/2025_12_28_140200_create_inscricoes_table.php
git add database/migrations/2025_12_28_140300_create_frequencias_table.php
git add database/migrations/2025_12_29_000002_create_orgao_user_table.php
git add database/migrations/2026_01_28_000000_create_support_tickets_table.php
git add database/migrations/2026_02_02_000001_create_ai_tables.php
git add database/migrations/2026_02_10_100002_create_rat_relato_recursos_table.php
git add database/migrations/2026_02_10_100003_create_rat_recursos_empregados_table.php
git add database/migrations/2026_02_11_000002_create_audit_logs_table.php
git add database/migrations/2026_02_19_000001_create_planos_contingencia_table.php
git add database/migrations/2026_02_24_000001_create_webhook_events_table.php
git add database/migrations/2026_03_10_000002_recreate_rat_polymorphic_tables.php
git add database/migrations/2026_03_11_000003_create_rat_tipos_table.php
git commit -m "refactor: convert RAT/webhook/other module ENUMs to string+checkIn for PostgreSQL"
```

---

## Task 5: Raw SQL MySQL-only — 3 arquivos de codigo

**Files:**
- Modify: `app/Modules/Decretacoes/Services/ProcessoQueryService.php` (linhas 739 e 960)
- Modify: `app/Modules/Decretacoes/Services/ProcessoExportBIService.php` (linha 128)
- Modify: `app/Http/Controllers/Api/HealthCheckController.php` (linha 364)
- Modify: `app/Modules/Pae/Services/PaeProtocoloService.php` (linha 62)

- [ ] **Step 1: Corrigir CAST(... AS UNSIGNED) — 3 locais**

`UNSIGNED` e tipo MySQL-only. No PostgreSQL, substituir por `BIGINT`:

```php
// ANTES (ProcessoQueryService.php:739, :960 e ProcessoExportBIService.php:128)
DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')

// DEPOIS
DB::raw('CAST(COALESCE(ed.valor, 0) AS BIGINT) as valor_numerico')
```

Aplicar nos 3 arquivos nas linhas indicadas.

- [ ] **Step 2: Corrigir SUBSTRING_INDEX em PaeProtocoloService.php**

`SUBSTRING_INDEX` e MySQL-only. O objetivo e extrair o ultimo segmento apos o ultimo `.` em `num_protocolo` (formato: `dd.mm.YYYY.NNN`).

```php
// ANTES (linha 62)
\Illuminate\Support\Facades\DB::raw(
    "CAST(SUBSTRING_INDEX(num_protocolo, '.', -1) AS UNSIGNED)"
)

// DEPOIS
\Illuminate\Support\Facades\DB::raw(
    "CAST(SPLIT_PART(num_protocolo, '.', 4) AS BIGINT)"
)
```

**Nota:** `SPLIT_PART(str, delim, N)` no PostgreSQL e 1-based. O protocolo tem formato `dd.mm.YYYY.NNN` — posicao 4 e sempre o sequencial. Se o formato puder ter mais segmentos, usar expressao mais robusta:
```php
DB::raw("CAST(REGEXP_REPLACE(num_protocolo, '^.*\\.', '') AS BIGINT)")
```

- [ ] **Step 3: Corrigir SHOW STATUS em HealthCheckController.php**

`SHOW STATUS` e exclusivo do MySQL. Substituir pela query equivalente no PostgreSQL usando `pg_stat_activity`:

```php
// ANTES (linha 361-375)
private function getDatabaseConnections(): array
{
    try {
        $result = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Max_used_connections', 'Threads_running')");

        $connections = [];
        foreach ($result as $row) {
            $connections[strtolower($row->Variable_name)] = (int) $row->Value;
        }

        return [
            'active' => $connections['threads_connected'] ?? 0,
            'max_used' => $connections['max_used_connections'] ?? 0,
            'running' => $connections['threads_running'] ?? 0,
        ];
    } catch (\Exception $e) {
        return [

// DEPOIS
private function getDatabaseConnections(): array
{
    try {
        $result = DB::selectOne("
            SELECT
                COUNT(*) FILTER (WHERE state IS NOT NULL) AS active,
                COUNT(*) FILTER (WHERE state = 'active') AS running
            FROM pg_stat_activity
            WHERE datname = current_database()
        ");

        return [
            'active'   => (int) ($result->active ?? 0),
            'max_used' => 0,     // nao disponivel diretamente no Postgres sem pg_stat_bgwriter
            'running'  => (int) ($result->running ?? 0),
        ];
    } catch (\Exception $e) {
        return [
```

- [ ] **Step 4: Verificar que nao ha mais SQL MySQL-only no codigo**

```bash
docker compose exec app grep -rn "AS UNSIGNED\|SHOW STATUS\|SHOW TABLES\|SUBSTRING_INDEX\|GROUP_CONCAT\|DATE_FORMAT\|IFNULL" app/
```

Saida esperada: nenhuma linha (zero matches).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Decretacoes/Services/ProcessoQueryService.php
git add app/Modules/Decretacoes/Services/ProcessoExportBIService.php
git add app/Http/Controllers/Api/HealthCheckController.php
git add app/Modules/Pae/Services/PaeProtocoloService.php
git commit -m "fix: replace MySQL-only SQL functions with PostgreSQL equivalents"
```

---

## Task 6 (antes Task 5): JSON → JSONB (20 migrations)

**Files:**
- `database/migrations/2025_01_15_000001_create_tasks_table.php`
- `database/migrations/2025_01_15_000002_create_task_comments_table.php`
- `database/migrations/2025_01_15_000005_create_task_sla_definitions_table.php`
- `database/migrations/2025_01_15_000007_create_task_audit_logs_table.php`
- `database/migrations/2025_11_27_000001_create_webhook_logs_table.php`
- `database/migrations/2025_11_27_000002_create_integrations_table.php`
- `database/migrations/2025_12_23_000001_create_permission_audit_log_table.php`
- `database/migrations/2025_12_27_184510_create_orgaos_table.php`
- `database/migrations/2025_12_27_184613_create_processo_logs_table.php`
- `database/migrations/2025_12_28_000001_create_rats_table.php`
- `database/migrations/2025_12_28_120100_create_abrigos_table.php`
- `database/migrations/2026_02_02_000001_create_ai_tables.php`
- `database/migrations/2026_02_11_000002_create_audit_logs_table.php`
- `database/migrations/2026_02_12_130002_create_pae_form_templates_table.php`
- `database/migrations/2026_02_24_000001_create_webhook_events_table.php`
- `database/migrations/2026_03_02_164838_create_dec_entrada_processo_logs_table.php`
- `database/migrations/2026_03_09_000001_add_subentities_to_rats_table.php`
- `database/migrations/2026_03_09_000002_add_anexos_to_rats_table.php`
- `database/migrations/2026_03_11_000001_create_tenants_table.php`
- `database/migrations/2026_03_11_000004_create_rat_ocorrencia_historico_table.php`

- [ ] **Step 1: Substituicao em massa — todos os 20 arquivos**

Padrao universal (aplicar em cada arquivo):
```php
// ANTES
$table->json('COLUNA')->nullable();

// DEPOIS
$table->jsonb('COLUNA')->nullable();
```

Para colunas JSON que sao consultadas por conteudo (ex: `campos_customizados`, `metadata`, `payload`, `dados_anteriores`, `antes`, `depois`), adicionar GIN index apos a definicao da tabela:

```php
// Adicionar ao final do Schema::create, antes do fecha-parenteses:
$table->jsonb('campos_customizados')->nullable();
// GIN index — adicionar somente para colunas que terao queries @> ou ?
// Em migration separada ou inline:
Schema::table('tasks', function (Blueprint $table) {
    $table->rawIndex('campos_customizados gin_trgm_ops', 'idx_tasks_campos_gin');
});
// Nota: use rawIndex apenas se necessario — para armazenamento puro, jsonb sem index basta
```

**Regra simplificada para este plano:** Converter todos `->json()` para `->jsonb()`. Nao adicionar GIN index agora — adicionar apenas quando uma query real `whereJsonContains` for identificada em code review. Evitar over-engineering.

- [ ] **Step 2: Executar migrate:fresh**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: 130 migrations sem erro.

Verificar que colunas sao jsonb:
```bash
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT table_name, column_name, data_type FROM information_schema.columns
   WHERE table_schema = 'public' AND data_type = 'jsonb'
   ORDER BY table_name, column_name;"
```

Saida esperada: lista de todas as colunas convertidas.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2025_01_15_000001_create_tasks_table.php
git add database/migrations/2025_01_15_000002_create_task_comments_table.php
git add database/migrations/2025_01_15_000005_create_task_sla_definitions_table.php
git add database/migrations/2025_01_15_000007_create_task_audit_logs_table.php
git add database/migrations/2025_11_27_000001_create_webhook_logs_table.php
git add database/migrations/2025_11_27_000002_create_integrations_table.php
git add database/migrations/2025_12_23_000001_create_permission_audit_log_table.php
git add database/migrations/2025_12_27_184510_create_orgaos_table.php
git add database/migrations/2025_12_27_184613_create_processo_logs_table.php
git add database/migrations/2025_12_28_000001_create_rats_table.php
git add database/migrations/2025_12_28_120100_create_abrigos_table.php
git add database/migrations/2026_02_02_000001_create_ai_tables.php
git add database/migrations/2026_02_11_000002_create_audit_logs_table.php
git add database/migrations/2026_02_12_130002_create_pae_form_templates_table.php
git add database/migrations/2026_02_24_000001_create_webhook_events_table.php
git add database/migrations/2026_03_02_164838_create_dec_entrada_processo_logs_table.php
git add database/migrations/2026_03_09_000001_add_subentities_to_rats_table.php
git add database/migrations/2026_03_09_000002_add_anexos_to_rats_table.php
git add database/migrations/2026_03_11_000001_create_tenants_table.php
git add database/migrations/2026_03_11_000004_create_rat_ocorrencia_historico_table.php
git commit -m "refactor: convert all json columns to jsonb for PostgreSQL binary storage"
```

---

## Task 7: Remover useCurrentOnUpdate (3 migrations)

**Files:**
- Modify: `database/migrations/2026_02_12_130215_create_pae_ccpae_table.php`
- Modify: `database/migrations/2026_02_12_130429_create_pae_form_itens_table.php`
- Modify: `database/migrations/2026_02_12_131458_create_pae_tramit_prot_table.php`

- [ ] **Step 1: Remover useCurrentOnUpdate nos 3 arquivos**

Em cada arquivo, localizar a linha com `->useCurrentOnUpdate()` e remover apenas essa chamada encadeada:

```php
// ANTES
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
// ou
$table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

// DEPOIS
$table->timestamp('updated_at')->useCurrent();
// ou
$table->timestamp('updated_at')->nullable();
```

Se o arquivo usa `$table->timestamps()` em vez de `timestamp` manual, nao ha nada a mudar — `timestamps()` nao usa useCurrentOnUpdate.

- [ ] **Step 2: Executar migrate:fresh**

```bash
docker compose exec app php artisan migrate:fresh
```

Saida esperada: 130 migrations, nenhum erro.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_02_12_130215_create_pae_ccpae_table.php
git add database/migrations/2026_02_12_130429_create_pae_form_itens_table.php
git add database/migrations/2026_02_12_131458_create_pae_tramit_prot_table.php
git commit -m "refactor: remove MySQL-only useCurrentOnUpdate from PAE migrations"
```

---

## Task 8: Validacao Final

**Files:** nenhum arquivo editado — apenas validacao.

- [ ] **Step 1: migrate:fresh + seed completos**

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Saida esperada:
```
  Dropping all tables .........................................  DONE
  Running migrations .........................................  DONE
  Running seeders ............................................  DONE
```

Sem erros de FK, sem erros de constraint, sem erros de tipo.

- [ ] **Step 2: Verificar schema final**

```bash
# Confirmar: nenhum ENUM nativo
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = 'public' AND udt_name = 'USER-DEFINED';"
# Esperado: 0

# Confirmar: colunas jsonb existem
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = 'public' AND data_type = 'jsonb';"
# Esperado: > 0 (pelo menos 20+)

# Confirmar: CHECK constraints existem
docker compose exec db psql -U sdc -d sdc -c \
  "SELECT COUNT(*) FROM pg_constraint WHERE contype = 'c';"
# Esperado: > 20 (um por coluna ENUM convertida)
```

- [ ] **Step 3: Smoke test — login e rotas principais**

```bash
docker compose exec app php artisan route:list --columns=method,uri,name | head -30
```

Saida esperada: lista de rotas sem erro.

```bash
docker compose exec app php artisan tinker --execute="
  echo 'DB driver: ' . DB::connection()->getDriverName() . PHP_EOL;
  echo 'Users count: ' . \App\Models\User::count() . PHP_EOL;
"
```

Saida esperada:
```
DB driver: pgsql
Users count: [N]
```

- [ ] **Step 4: Commit final**

```bash
git add -A
git commit -m "chore: PostgreSQL migration complete — 130 migrations, all ENUMs and JSON converted"
```

---

## Checklist de Conclusao

- [ ] `docker compose exec app php artisan migrate:fresh --seed` roda sem erro
- [ ] Driver reportado e `pgsql` (nao `mysql`)
- [ ] Zero colunas `USER-DEFINED` (ENUM nativo) no schema
- [ ] 20+ colunas `jsonb` no schema
- [ ] 20+ CHECK constraints no schema
- [ ] Login funciona pela interface
- [ ] Rotas dos modulos RAT, PAE, TDAP carregam sem 500
