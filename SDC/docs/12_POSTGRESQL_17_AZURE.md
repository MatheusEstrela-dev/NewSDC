# 12. PostgreSQL 17 — Azure Flexible Server

Documentacao do estado atual da camada de dados do SDC apos a consolidacao em Azure Database for PostgreSQL Flexible Server PG 17.9. Foca apenas no que existe hoje no banco e no codigo — nao cobre historico de transicao.

---

## Stack de banco de dados

O SDC opera em **dois clusters PostgreSQL** separados por workload:

| Cluster | Onde roda | Versao | Extensoes principais | Workload |
|---|---|---|---|---|
| **Producao (`sdc`)** | Azure Database for PostgreSQL Flexible Server | PG **17.9** | vector, postgis, btree_gin, pg_trgm, pgcrypto, unaccent, uuid-ossp, pg_stat_statements | App principal (transacional) |
| **Dev / IA (`sdc_ai`)** | Container Docker `db_ai` | PG **17** + Citus 12.1 | citus, vector 0.8, postgis 3 | Embeddings, sharding local, paridade dev |

**Host de producao:** `newsdc.postgres.database.azure.com:5432` — sslmode `require`.

> Citus nao esta disponivel em Flexible Server. O codebase trata a extensao como condicional: presente em dev local, ausente em prod. Para sharding em Azure considerar particionamento declarativo nativo PG17 + `pg_partman`.

---

## Extensoes habilitadas (producao)

Habilitadas via `azure.extensions` (Server parameters do Azure) + `CREATE EXTENSION IF NOT EXISTS` na migration `2026_05_05_000001_enable_postgres_extensions`.

| Extensao | Versao | Uso no SDC |
|---|---|---|
| `vector` | 0.8.2 | Embeddings RAG (`dec_embeddings`, dimensao 768, indice `ivfflat`) |
| `postgis` | 3.6.1 | Geolocalizacao de ocorrencias e abrigos |
| `btree_gin` | 1.3 | Indices GIN compostos em `(status, created_at)` para listas filtradas |
| `pg_trgm` | 1.6 | Busca fuzzy em `num_protocolo`, `sigibar`, `sei_numero`, `titulo` de tasks |
| `pgcrypto` | 1.3 | Hash, UUID, criptografia simetrica em campos sensiveis |
| `unaccent` | 1.1 | Normalizacao de busca textual (acentos) |
| `uuid-ossp` | 1.1 | Geracao de UUIDs |
| `pg_stat_statements` | 1.11 | Telemetria de queries (tuning) |

---

## Tabelas criadas (118 no schema `public`)

### Core / Auth (12)
`users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `permission_audit_log`, `personal_access_tokens`, `password_reset_tokens`, `tenants`, `orgaos`, `orgao_user`

### PAE — Protocolos (16)
`pae_protocolos`, `pae_analises`, `pae_notificacoes`, `pae_timeline`, `pae_dilacoes`, `pae_ccpae`, `pae_coordenador`, `pae_datas_ciclos`, `pae_polif`, `pae_empdors`, `pae_empntos`, `pae_forms`, `pae_form_templates`, `pae_form_apontamentos`, `pae_form_conclusao`, `pae_tramit_prot`

### Decretacoes (DEC) (16)
`dec_processo`, `dec_entrada_processos`, `dec_entrada_decretos`, `dec_entrada_desastres`, `dec_entrada_categoria_desastres`, `dec_entrada_processo_logs`, `dec_decreto_categorias`, `dec_decreto_municipios`, `dec_desastre_categorias`, `dec_desastre_grupos`, `dec_desastre_items`, `dec_desastre_item_campos`, `dec_cobrade`, `dec_log`, `dec_permissao`, `dec_embeddings`

### RAT — Registro de Atendimento (16)
`rats`, `rat_anexos`, `rat_ocorrencias`, `rat_ocorrencia_relatos`, `rat_ocorrencia_historico`, `rat_ocorrencia_historicos`, `rat_relato_dados_gerais`, `rat_relato_envolvidos`, `rat_relato_recursos`, `rat_relato_vistoria`, `rat_relato_vistorias`, `rat_recursos_componentes_guarnicao`, `rat_recursos_empregados`, `rat_redec`, `rat_tipos`, `rat_veiculos`

### Processos administrativos / municipios (9)
`processos`, `processo_anexos`, `processo_logs`, `processo_municipios`, `processo_danos_humanos`, `processo_danos_materiais`, `processo_prejuizos`, `municipios`, `cedec_municipio`

### Tasks / SLA (7)
`tasks`, `task_comments`, `task_attachments`, `task_approvals`, `task_audit_logs`, `task_sla_definitions`, `task_sla_instances`

### Modulo IA (4)
`ai_conversations`, `ai_messages`, `ai_execution_logs`, `dec_embeddings` (vector(768) + ivfflat)

### TDAP — Almoxarifado (6)
`tdap_products`, `tdap_product_lotes`, `tdap_product_compositions`, `tdap_recebimentos`, `tdap_recebimento_itens`, `tdap_movimentacoes`

### Operacional / Apoio (17)
`abrigos`, `beneficiarios`, `beneficiario_abrigo`, `membros_familia`, `doacoes`, `itens_doacao`, `auxilios`, `itens_auxilio`, `estoques`, `movimentacoes_estoque`, `movimentacoes_financeiras`, `frequencias`, `inscricoes`, `treinamentos`, `plantoes`, `planos_contingencia`, `estacoes_meteorologicas`

### Notificacoes / Webhooks / Logs (8)
`notifications`, `user_notification_preferences`, `webhook_logs`, `webhook_events`, `integrations`, `audit_logs`, `failed_jobs`, `user_status_histories`

### Suporte (3)
`support_tickets`, `support_ticket_messages`, `migrations`

### Telescope (3)
`telescope_entries`, `telescope_entries_tags`, `telescope_monitoring`

### Sistema PostGIS
`spatial_ref_sys` (referencia de coordenadas)

---

## Triggers automaticos de `updated_at`

Migration `2026_05_05_000002_add_updated_at_triggers` cria duas funcoes plpgsql e aplica triggers `BEFORE UPDATE`:

- `set_updated_at_column()` -> `NEW.updated_at = NOW()`
- `set_data_atualizacao_column()` -> `NEW.data_atualizacao = NOW()`

Triggers ativos hoje:

- `trg_rat_relato_envolvidos_set_updated_at` em `rat_relato_envolvidos`
- `trg_rat_relato_recursos_set_updated_at` em `rat_relato_recursos`
- `trg_rat_relato_vistorias_set_updated_at` em `rat_relato_vistorias`

Garantem consistencia de `updated_at` mesmo em UPDATEs feitos via SQL direto fora do Eloquent.

---

## Timezone — America/Sao_Paulo (GMT-3)

Aplicado em tres niveis para que datas em logs, tabelas e APIs apareçam em horario Brasilia:

1. **Database Azure** — `ALTER DATABASE sdc SET TIMEZONE = 'America/Sao_Paulo'`
2. **Conexao Laravel** — bloco `pgsql` em `config/database.php` define `'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo')` (Laravel emite `SET TIMEZONE` apos cada connect)
3. **PHP** — `config/app.php` `'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo')`

Validacao: `SELECT NOW()` retorna `2026-05-04 13:45:43-03` (offset `-03` confirmado).

> Para fixar timezone no servidor inteiro (jobs administrativos, backups, statistics_collector), setar tambem o server parameter `TimeZone = America/Sao_Paulo` no Portal Azure.

---

## Helper de compatibilidade — `PgCompat`

Classe estatica em `app/Support/Database/PgCompat.php` que centraliza expressoes SQL portaveis. Usada em qualquer `selectRaw`/`whereRaw`/`orderByRaw` que envolva funcoes especificas do dialeto.

```php
use App\Support\Database\PgCompat;

// EXTRACT em PG vs MONTH() em MySQL
$mes = PgCompat::extractDatePart('MONTH', 'created_at');
RatOcorrencia::selectRaw("{$mes} as mes, count(*) as total")
    ->groupBy('mes')->get();

// SPLIT_PART em PG vs SUBSTRING_INDEX em MySQL
$col = PgCompat::splitPart('num_protocolo', '.', 4);
PaeProtocolo::max(DB::raw(PgCompat::castToInt($col)));

// Diferenca de dias e CURRENT_DATE
PgCompat::dateDiffDays('dt_devolutiva', 'NOW()');
PgCompat::currentDate();
```

Metodos disponiveis: `splitPart`, `extractDatePart`, `dateDiffDays`, `dateAddDays`, `castToInt`, `currentDate`, `isPgsql`.

---

## Configuracao de conexao Laravel

`config/database.php` define duas conexoes Postgres:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'sdc'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'search_path' => env('DB_SEARCH_PATH', 'public'),
    'sslmode' => env('DB_SSLMODE', 'prefer'),
    'sslrootcert' => env('DB_SSL_CA') ?: null,
    'application_name' => env('APP_NAME', 'sdc-laravel'),
    'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
    'options' => [PDO::ATTR_EMULATE_PREPARES => false],
],

'pgsql_read' => [
    // db_ai container ou read-replica do Azure
    'driver' => 'pgsql',
    'host' => env('DB_PGSQL_HOST', env('DB_HOST')),
    'database' => env('DB_PGSQL_DATABASE', env('DB_DATABASE', 'sdc_ai')),
    'application_name' => env('APP_NAME', 'sdc-laravel') . '-ai',
    // ...demais campos analogos
],
```

---

## `.env` de producao (Azure)

```ini
DB_CONNECTION=pgsql
DB_HOST=newsdc.postgres.database.azure.com
DB_PORT=5432
DB_DATABASE=sdc
DB_USERNAME=newsdc
DB_PASSWORD=*****
DB_SSLMODE=require
DB_SSL_CA=
DB_SEARCH_PATH=public
DB_TIMEZONE=America/Sao_Paulo
APP_TIMEZONE=America/Sao_Paulo
```

---

## Tabela `dec_embeddings` (RAG)

Unica tabela criada via SQL raw porque depende do tipo `vector` da pgvector:

```sql
CREATE TABLE dec_embeddings (
    id          bigserial    PRIMARY KEY,
    content     text         NOT NULL,
    embedding   vector(768),
    source_type varchar(100) NOT NULL DEFAULT 'rat',
    source_name varchar(255) NOT NULL DEFAULT '',
    chunk_number int         NOT NULL DEFAULT 0,
    metadata    jsonb,
    created_at  timestamp    DEFAULT now()
);

CREATE INDEX dec_embeddings_embedding_idx
    ON dec_embeddings
    USING ivfflat (embedding vector_cosine_ops)
    WITH (lists = 100);
```

Dimensao 768 alinhada com `gemini-embedding` / `text-embedding-004`. Indice `ivfflat` cosine acelera `ORDER BY embedding <=> query`.

---

## Indices de performance especiais

Migration `2026_03_25_120000_add_performance_indexes`:

- **GIN trigram** em colunas textuais de busca (`pae_protocolos.num_protocolo`, `processos.n_protocolo_fide`, `tasks.titulo`, etc.)
- **GIN composto** em `(status, created_at)` para listas paginadas filtradas — usa `btree_gin` quando disponivel, senao cai em `BTREE` composto (fallback equivalente em performance para a maioria dos casos)

A migration e tolerante: se a allowlist do Azure nao incluir alguma extensao, faz `try/catch` silencioso e segue o resto.

---

## Pre-requisitos para reaplicar em outro Flexible Server

No Portal Azure, antes de rodar `php artisan migrate`:

1. **Server parameters → `azure.extensions`** — habilitar lista:
   `VECTOR, PG_TRGM, PGCRYPTO, UUID-OSSP, UNACCENT, PG_STAT_STATEMENTS, POSTGIS, POSTGIS_RASTER, POSTGIS_TOPOLOGY, BTREE_GIN, PG_PARTMAN`
2. **Criar a database**: `CREATE DATABASE sdc;`
3. **Server parameters → `TimeZone = America/Sao_Paulo`** (opcional, recomendado)
4. **Networking** — liberar IP da app/Container Apps/Jenkins runner

Depois: `php artisan migrate --force` aplica as 135 migrations idempotentemente.

---

## Migrations especificas Postgres

- `2026_05_05_000001_enable_postgres_extensions.php` — habilita as 8 extensoes (idempotente, tolerante a allowlist incompleta).
- `2026_05_05_000002_add_updated_at_triggers.php` — funcoes plpgsql + triggers `BEFORE UPDATE` em tabelas RAT.
- `2026_04_27_000001_create_dec_embeddings_table.php` — tabela RAG com `vector(768)` e indice ivfflat.
- `2026_03_25_120000_add_performance_indexes.php` — GIN trigram + GIN composto / BTREE fallback.

---

## Validacao rapida

Comandos uteis para inspecionar estado do banco:

```bash
# Listar extensoes ativas
psql "host=newsdc.postgres.database.azure.com user=newsdc dbname=sdc sslmode=require" \
  -c "SELECT extname, extversion FROM pg_extension ORDER BY extname;"

# Contar tabelas e migrations
psql ... -c "SELECT (SELECT count(*) FROM pg_tables WHERE schemaname='public') AS tabelas,
                    (SELECT count(*) FROM migrations) AS migrations;"

# Verificar timezone
psql ... -c "SELECT current_setting('TimeZone'), NOW();"

# Listar triggers do projeto
psql ... -c "SELECT tgname, tgrelid::regclass FROM pg_trigger WHERE tgname LIKE 'trg_%';"
```
