# Design: Migracao MySQL -> PostgreSQL para NewSDC (v2)

**Data:** 2026-04-27
**Autor:** Matheus Estrela
**Status:** Aprovado
**Substitui:** `2026-04-17-mysql-postgres-newSDC-design.md` (numeros desatualizados)

---

## Contexto e Motivacao

O NewSDC nasce como ambiente greenfield em PostgreSQL nativo. A motivacao e aproveitar
JSONB com GIN indexes, CHECK constraints nativas e melhor desempenho em queries sobre
dados semi-estruturados. O SDC de producao (MySQL) nao e tocado — repositorio separado.

---

## Escopo

- **In scope:** migrations, config/database.php, docker-compose, modelos com raw SQL MySQL-only
- **Out of scope:** logica de negocio, APIs, views, autorizacao, SDC-producao
- **Downtime:** flexivel — ambiente novo, sem dados de producao em risco

---

## Arquitetura da Solucao

### Abordagem escolhida: Script Artisan de substituicao em massa

Em vez de editar 59+ arquivos manualmente, um comando Artisan (`db:port-postgres`) faz as
substituicoes mecanicas em lote. Excecoes complexas (raw SQL, config, docker) ficam como
edicoes manuais explicitas.

```
php artisan db:port-postgres --dry-run    mostra diff sem escrever
php artisan db:port-postgres              aplica substituicoes
php artisan db:port-postgres --batch=json|enum|timestamps  categoria especifica
```

### O que muda

| Categoria | Volume real | Acao |
|-----------|-------------|------|
| Docker / config / env | 3 arquivos | Substituicao manual |
| Conexoes legado (legacy, carga, tenancy) | 3 conexoes | Remover de config/database.php |
| ENUMs em migrations | 22 arquivos | Script: ->enum() -> string + checkIn() |
| JSON em migrations | 15+ arquivos | Script: ->json() -> ->jsonb() + GIN index |
| useCurrentOnUpdate | 3 arquivos | Script: remover chamada |
| Raw SQL MySQL-only | 5 arquivos PHP | Manual: substituicoes cirurgicas |

### O que NAO muda

- Logica de negocio, controllers, services (exceto raw SQL)
- Eloquent models — $casts = ['campo' => 'array'] funciona igual com JSONB
- Views, rotas, autorizacao

---

## Detalhes Tecnicos por Categoria

### 1. Docker e Infra

```yaml
# docker/docker-compose.yml — bloco db:
db:
  image: postgres:16-alpine
  container_name: newsdc_db
  hostname: db
  restart: unless-stopped
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

Remover: servico `mysql-exporter`, secret `db_root_password`, volume `/var/lib/mysql`,
arquivo `./mysql/dev.cnf`, argumento `--default-authentication-plugin=...`.

```php
// config/database.php
'default' => env('DB_CONNECTION', 'pgsql'),

// REMOVER completamente os blocos:
'legacy'  => [...],
'carga'   => [...],
'tenancy' => [...],

// MANTER apenas: pgsql, sqlite (testes), sqlsrv se necessario
```

```env
# docker/env.example
DB_CONNECTION=pgsql
DB_PORT=5432
```

**Auditoria obrigatoria antes de remover conexoes legado:**
```bash
grep -rn "connection('legacy')\|connection('carga')\|connection('tenancy')" app/
```
Qualquer ocorrencia deve ser removida ou portada para pgsql antes do deploy.

### 2. Script Artisan: db:port-postgres

**Batch JSON -> JSONB + GIN:**

```php
// ANTES
$table->json('dados')->nullable();

// DEPOIS
$table->jsonb('dados')->nullable();
$table->index('dados', 'idx_TABELA_dados', 'gin');
```

GIN index aplicado em TODAS as colunas JSONB sem excecao (decisao de negocio: esquema
otimizado desde o inicio, sem adicoes posteriores).

**Batch ENUM -> string + checkIn:**

```php
// ANTES
$table->enum('status', ['ativo', 'inativo'])->default('ativo');

// DEPOIS
$table->string('status', 20)->default('ativo')
      ->checkIn(['ativo', 'inativo']);

// N = len(valor_mais_longo) + 10, minimo 20
// Modificadores encadeados (->default, ->nullable, ->index, ->comment) sao preservados
```

**Batch useCurrentOnUpdate:**

```php
// ANTES
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

// DEPOIS
$table->timestamp('updated_at')->useCurrent();
// Eloquent gerencia updated_at via Model::save()
```

**Verificacao automatica ao final do script:**
```bash
# Todos devem retornar 0
grep -rn "->json("             database/migrations/ | wc -l
grep -rn "->enum("             database/migrations/ | wc -l
grep -rn "useCurrentOnUpdate"  database/migrations/ | wc -l
```

### 3. Raw SQL MySQL-only — 5 arquivos (edicao manual)

| Arquivo | Linha | Problema | Fix PostgreSQL |
|---------|-------|----------|----------------|
| `ProcessoQueryService.php` | 713, 934 | `CAST(... AS UNSIGNED)` | `CAST(... AS BIGINT)` |
| `ProcessoExportBIService.php` | 128 | `CAST(... AS UNSIGNED)` | `CAST(... AS BIGINT)` |
| `ProcessoExportService.php` | 149 | `CAST(... AS UNSIGNED)` | `CAST(... AS BIGINT)` |
| `HealthCheckController.php` | 364 | `SHOW STATUS WHERE ...` | `SELECT ... FROM pg_stat_activity` |
| `PaeProtocoloService.php` | 62 | `SUBSTRING_INDEX(..., '.', -1)` | `SPLIT_PART(num_protocolo, '.', 4)` |

Detalhes:

```php
// HealthCheckController.php — substituir getDatabaseConnections()
$result = DB::selectOne("
    SELECT
        COUNT(*) FILTER (WHERE state IS NOT NULL) AS active,
        COUNT(*) FILTER (WHERE state = 'active') AS running
    FROM pg_stat_activity
    WHERE datname = current_database()
");
return [
    'active'   => (int) ($result->active ?? 0),
    'max_used' => 0,
    'running'  => (int) ($result->running ?? 0),
];

// PaeProtocoloService.php — protocolo formato dd.mm.YYYY.NNN
DB::raw("CAST(SPLIT_PART(num_protocolo, '.', 4) AS BIGINT)")
```

### 4. Lacunas corrigidas vs plano anterior (2026-04-17)

| Lacuna | Plano anterior | Corrigido aqui |
|--------|---------------|----------------|
| ProcessoExportService.php | Nao listado | Incluido em Task raw SQL |
| Migration orgaos | `2025_12_27_184510_create_orgaos_table.php` (inexistente) | `2014_01_01_000000_create_orgaos_table.php` |
| ENUM `module` em notification_preferences | Nao mapeado | Coberto pelo script batch |
| Conexoes legacy/carga/tenancy | Manter | Remover todas |
| Numeros do inventario | 8 ENUMs, 8 JSON (errados) | 22 migrations ENUM, 15+ migrations JSON |

---

## Fases de Execucao

```
Fase 1: Infra (manual)
  docker-compose: mysql -> postgres:16-alpine
  config/database.php: default pgsql, remover conexoes legado
  env.example: DB_CONNECTION=pgsql, DB_PORT=5432
  Auditoria: grep DB::connection('legacy|carga|tenancy')

Fase 2: Script db:port-postgres (automatizado)
  Criar o comando Artisan
  Rodar --dry-run, revisar diff
  Rodar --batch=json, depois --batch=enum, depois --batch=timestamps
  Verificar: todos os greps retornam 0

Fase 3: Raw SQL (manual, 5 arquivos)
  ProcessoQueryService.php (2 locais)
  ProcessoExportBIService.php (1 local)
  ProcessoExportService.php (1 local)
  HealthCheckController.php (1 local)
  PaeProtocoloService.php (1 local)
  Verificar: grep -rn "AS UNSIGNED|SHOW STATUS|SUBSTRING_INDEX" app/

Fase 4: Validacao
  docker compose up -d db && pg_isready
  php artisan migrate:fresh --seed
  Smoke test: login, RAT, PAE, TDAP, Decretacoes
```

---

## Criterios de Conclusao

| Criterio | Verificacao |
|----------|-------------|
| Zero ENUMs nativos no schema | `SELECT COUNT(*) FROM information_schema.columns WHERE udt_name = 'USER-DEFINED'` = 0 |
| 20+ colunas jsonb | `SELECT COUNT(*) FROM information_schema.columns WHERE data_type = 'jsonb'` >= 20 |
| 20+ CHECK constraints | `SELECT COUNT(*) FROM pg_constraint WHERE contype = 'c'` >= 20 |
| 20+ GIN indexes | `SELECT COUNT(*) FROM pg_indexes WHERE indexdef LIKE '%gin%'` >= 20 |
| Driver correto | `DB::connection()->getDriverName()` = pgsql |
| migrate:fresh --seed sem erro | Saida: DONE sem exception |
| Login funciona | HTTP 200 na rota /login |
| Modulos sem 500 | RAT, PAE, TDAP, Decretacoes carregam |

---

## Riscos

| Risco | Prob | Mitigacao |
|-------|------|-----------|
| Regex do script pegar falso-positivo | Media | --dry-run obrigatorio antes de aplicar |
| DB::connection('legacy') oculto em service provider | Baixa | grep antes de remover conexoes |
| Ordem de FK errada nas migrations | Media | migrate:fresh itera erros um por um |
| JSONB quebrar cast de array existente | Muito baixa | Laravel abstrai via $casts |
