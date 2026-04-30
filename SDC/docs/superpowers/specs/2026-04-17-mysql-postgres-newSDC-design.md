# Design: Migracao MySQL → PostgreSQL para NewSDC

**Data:** 2026-04-17
**Autor:** Matheus Estrela
**Status:** Aprovado

---

## Contexto e Motivacao

O NewSDC e um novo ambiente (greenfield deployment) que nasce nativamente em PostgreSQL. A motivacao tecnica e aproveitar os tipos binarios otimizados do Postgres — especialmente `JSONB` com GIN indexes, CHECK constraints nativas e melhor desempenho em queries sobre dados semi-estruturados.

O SDC de producao (MySQL) nao e tocado. O NewSDC porta as migrations e models existentes, refatorando-as para serem Postgres-native.

---

## Escopo

- **In scope:** migrations, models, controllers com raw queries, config/database.php, docker-compose
- **Out of scope:** logica de negocio, APIs, views, regras de autorizacao — nada disso muda
- **Volume de dados:** medio (1–20 GB), ambiente interno governamental
- **Downtime:** flexivel — novo ambiente, sem dados de producao

---

## Arquitetura da Camada de Dados

O NewSDC mantem a estrutura Laravel padrao (Eloquent + migrations). Apenas o contrato com o banco muda.

```
SDC (prod, MySQL)          NewSDC (novo, Postgres)
─────────────────          ──────────────────────────
migrations/                migrations/  <── refatoradas
models/          ────►     models/      <── casts JSONB
controllers/               controllers/ <── raw queries corrigidas
config/database.php        config/database.php  <── default: pgsql
docker-compose.yml         docker-compose.yml   <── postgres:16-alpine
```

**Configuracao de conexao:**
- `DB_CONNECTION=pgsql`
- Remover conexoes `mysql` e `sdc` do `config/database.php`
- Docker: `postgres:16-alpine`
- Porta: 5432

---

## Inventario de Mudancas

| Categoria | Quantidade | Acao |
|-----------|-----------|------|
| ENUMs | 8 colunas em 7 migrations | `string` + `checkIn()` (CHECK constraint) |
| JSON → JSONB | 8 colunas em 4 tabelas | `jsonb` + GIN index |
| `useCurrentOnUpdate()` | 7 instancias | Remover — Eloquent cobre `updated_at` |
| INFORMATION_SCHEMA queries | 2 (ConfigController) | Reescrever com `pg_catalog` |
| `DB::raw()` com CONCAT/COUNT/SUM | ~42 instancias | Auditoria — maioria compativel sem mudanca |
| Config e Docker | 3 arquivos | Substituir MySQL por Postgres |

---

## Plano de Refatoracao por Categoria

### 1. ENUMs → CHECK Constraints

PostgreSQL nao tem coluna ENUM simples. A abordagem e `string` + CHECK constraint via `checkIn()` do Laravel Schema Builder — mantem integridade sem lookup table.

```php
// ANTES (MySQL)
$table->enum('recurso_tipo', ['viatura', 'pe', 'aereo', 'aquatico', 'outro']);

// DEPOIS (Postgres-native)
$table->string('recurso_tipo', 20)
      ->checkIn(['viatura', 'pe', 'aereo', 'aquatico', 'outro']);
```

**Colunas afetadas:**

| Migration | Tabela | Coluna | Valores |
|-----------|--------|--------|---------|
| 2023_11_29_163051 | `logs` | `level` | 8 niveis PSR-3 |
| 2023_11_30_162543 | `logs_ac2023` | `level` | 8 niveis PSR-3 |
| 2025_01_13_000001 | `rat_relato_recursos` | `recurso_tipo` | viatura, pe, aereo, aquatico, outro |
| 2025_09_30_093343 | `rat_relato_recursos` | `recurso_tipo` | idem |
| 2025_09_30_093343 | `rat_relato_recursos` | `viatura_condicao` | 5 condicoes |
| 2025_07_01_125638 | `tdap_vistoria` | `parecer` | Aprovado, Reprovado |
| 2025_12_09_121758 | `rat_recursos_empregados` | `recurso_tipo` | 5 tipos |
| 2026_01_15_131255 | `rat_audit_log` | `acao` | created, updated, deleted, restored |

---

### 2. JSON → JSONB com GIN Index

Colunas `json` existentes devem ser convertidas para `jsonb` (armazenamento binario, indexavel).

```php
// ANTES
$table->json('dados');

// DEPOIS
$table->jsonb('dados');
$table->index('dados', null, 'gin');
```

**Colunas afetadas (8 colunas em 4 tabelas):**

| Migration | Tabela | Colunas |
|-----------|--------|---------|
| 2025_05_16_104843 | `pae_formularios` | `respostas`, `apontamentos`, `conclusao` |
| 2025_07_29_000001 | `dec_entrada_processo_logs` | `entrada_processo_data` |
| 2026_01_15_110000 | `pae_form_templates` | `sub_itens_json` |
| 2026_01_15_131255 | `rat_audit_log` | `dados_anteriores`, `dados_posteriores`, `campos_alterados` |

Models com `$casts = ['campo' => 'array']` nao precisam de mudanca — o Laravel abstrai o tipo.

---

### 3. `useCurrentOnUpdate()` — Remover

`ON UPDATE CURRENT_TIMESTAMP` e exclusivo do MySQL. O Eloquent gerencia `updated_at` automaticamente via `Model::save()`. Remover o metodo das 7 instancias encontradas:

- `2019_08_19_000000_create_failed_jobs_table.php`
- `2026_01_13_100001_create_pae_timeline_table.php`
- `2026_01_13_100002_create_pae_dilacoes_table.php`
- `2026_01_13_100003_create_pae_ccpae_table.php`
- `2026_01_13_100005_create_pae_form_itens_table.php`
- `2026_01_15_131255_create_rat_audit_log_table.php`
- (1 adicional a confirmar na auditoria)

```php
// REMOVER esta linha
->useCurrentOnUpdate()

// O timestamp continua com valor inicial via
->useCurrent()
// e Eloquent atualiza automaticamente no save()
```

---

### 4. INFORMATION_SCHEMA — ConfigController

Duas queries hardcoded com schema MySQL (`TABLE_SCHEMA = "dbsdc"`). Reescrever para `information_schema` padrao ANSI + `pg_catalog`:

```php
// ANTES
DB::select('SELECT TABLE_NAME, TABLE_COMMENT
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = "dbsdc"');

// DEPOIS
DB::select("SELECT table_name,
                   obj_description(
                       (quote_ident(table_name))::regclass,
                       'pg_class'
                   ) as table_comment
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_type = 'BASE TABLE'");
```

**Arquivo:** `app/Http/Controllers/Config/ConfigController.php` (linhas 111 e 130)

---

### 5. `DB::raw()` — Auditoria

Das ~42 instancias de `DB::raw()`, a maioria usa `CONCAT()`, `COUNT()`, `SUM()` — todos compativeis com Postgres sem mudanca. A auditoria deve confirmar que nenhuma usa funcoes exclusivas do MySQL (`IFNULL`, `IF()`, `GROUP_CONCAT`, `FIND_IN_SET`, `DATE_FORMAT`).

Substituicoes necessarias caso encontradas:

| MySQL | Postgres |
|-------|----------|
| `IFNULL(x, y)` | `COALESCE(x, y)` |
| `IF(cond, a, b)` | `CASE WHEN cond THEN a ELSE b END` |
| `GROUP_CONCAT(x)` | `STRING_AGG(x, ',')` |
| `DATE_FORMAT(d, '%Y')` | `TO_CHAR(d, 'YYYY')` |

---

## Configuracao de Infra

### docker-compose.yml

```yaml
services:
  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data

volumes:
  pgdata:
```

### config/database.php

```php
'default' => env('DB_CONNECTION', 'pgsql'),
```

Remover blocos `mysql` e `sdc`. Manter apenas `pgsql`, `sqlite` (para testes) e `sqlsrv` se necessario.

---

## Fases de Execucao

```
Fase 1: Infra
  docker-compose com postgres:16-alpine
  config/database.php → default pgsql
  .env → DB_CONNECTION=pgsql, DB_PORT=5432

Fase 2: Schema
  Refatorar 95 migrations
  ENUMs → checkIn()
  JSON → jsonb + GIN
  useCurrentOnUpdate → remover

Fase 3: Codigo
  ConfigController → pg_catalog queries
  DB::raw() → auditoria e correcoes pontuais

Fase 4: Validacao
  php artisan migrate:fresh
  php artisan db:seed
  Smoke test dos modulos RAT, PAE, TDAP, Decretacoes
```

---

## Criterios de Conclusao

| Fase | Criterio de Done |
|------|-----------------|
| Infra | `docker compose up` sobe Postgres sem erro |
| Schema | `php artisan migrate:fresh` roda 95 migrations sem erro |
| Codigo | `php artisan db:seed` completa; login e rotas basicas funcionam |
| Validacao | Modulos RAT, PAE, TDAP, Decretacoes carregam sem erro 500 |

---

## Riscos Residuais

| Risco | Probabilidade | Mitigacao |
|-------|--------------|-----------|
| `DB::raw()` com funcao MySQL nao mapeada | Baixa | Auditoria manual nas 42 instancias |
| Package de terceiro com SQL hardcoded | Baixa | `yajra/datatables` suporta Postgres |
| JSONB quebrar cast de array existente | Muito baixa | Laravel abstrai via `$casts` |
| Ordem de FK errada nas migrations | Media | Rodar `migrate:fresh` e corrigir iterativamente |

---

## Estimativa de Esforco

| Fase | Esforco |
|------|---------|
| Infra (docker, config) | 0,5 dia |
| Schema — 95 migrations | 3–4 dias |
| Codigo — raw queries e ConfigController | 1–2 dias |
| Validacao e testes | 2 dias |
| **Total** | **6–8 dias uteis** |
