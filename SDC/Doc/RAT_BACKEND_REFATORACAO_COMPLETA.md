# RAT — Documentação Completa da Arquitetura Backend

**Autora:** Barbara Costa
**Última atualização:** 11/03/2026
**Branch:** `feat/rat-backend`
**Status:** ✅ Implementação completa e operacional

---

## Índice

1. [Visão Geral](#1-visão-geral)
2. [Estrutura de Arquivos](#2-estrutura-de-arquivos)
3. [Banco de Dados — 15 Tabelas RAT](#3-banco-de-dados--15-tabelas-rat)
4. [Migrations](#4-migrations)
5. [Models — Hierarquia Completa](#5-models--hierarquia-completa)
6. [DTOs](#6-dtos)
7. [Form Requests (Validação)](#7-form-requests-validação)
8. [Services — Camada de Negócio](#8-services--camada-de-negócio)
9. [Controllers](#9-controllers)
10. [Rotas Registradas](#10-rotas-registradas)
11. [Permissionamento e Policy](#11-permissionamento-e-policy)
12. [Conexões de Banco de Dados](#12-conexões-de-banco-de-dados)
13. [Fluxo Completo de uma Requisição](#13-fluxo-completo-de-uma-requisição)
14. [Diagrama de Relacionamentos](#14-diagrama-de-relacionamentos)

---

## 1. Visão Geral

O módulo RAT (Relatório de Atendimento a Emergência) foi refatorado para uma arquitetura **polimórfica limpa**, seguindo o padrão "Padrão Ouro":

```
Requisição HTTP
      │
  Controller  ──→  FormRequest (validação de entrada)
      │
   DTO         ──→  tipagem estrita entre HTTP e domínio
      │
   Service     ──→  regras de negócio em transação DB
      │
   Model       ──→  entidades Eloquent com SoftDeletes
      │
   Database    ──→  MySQL InnoDB (ACID garantido)
```

### Princípio do Polimorfismo

Uma `RatOcorrencia` (protocolo) pode ter **N relatos** de tipos diferentes.
O vínculo é feito pela tabela pivô `rat_ocorrencia_relatos`, que armazena
`conteudo_id` + `conteudo_type` (morphTo do Eloquent):

```
RatOcorrencia (rat_ocorrencias)
      │  hasMany
      ▼
RatOcorrenciaRelato (rat_ocorrencia_relatos)
      │  morphTo('conteudo')
      ├──→ RatRelatoDadosGerais   (rat_relato_dados_gerais)
      ├──→ RatRelatoEnvolvidos    (rat_relato_envolvidos)
      ├──→ RatRelatoRecurso       (rat_relato_recursos)
      │         │  hasMany
      │         └──→ RatRecursosEmpregado (rat_recursos_empregados)
      │                   │  hasMany
      │                   └──→ RatRecursosComponentesGuarnicao
      └──→ RatRelatoVistoria      (rat_relato_vistoria)
```

### Cascade Soft Delete

- `RatOcorrencia::delete()` → cascateia para todos os `RatOcorrenciaRelato`
- `RatOcorrenciaRelato::delete()` → cascateia para o conteúdo polimórfico
- `RatRelatoRecurso::delete()` → cascateia para `RatRecursosEmpregado` e componentes
- Dados **nunca são apagados fisicamente** — apenas `deleted_at` é preenchido

---

## 2. Estrutura de Arquivos

```
app/
├── DTOs/Rat/
│   ├── RatBoDTO.php
│   ├── RatDadosGeraisDTO.php
│   └── RatOcorrenciaFiltroDTO.php
│
├── Http/
│   ├── Controllers/Compdec/
│   │   ├── RatController.php           ← CRUD principal (nova estrutura)
│   │   ├── RatOcorrenciaController.php ← ocorrências + relatos polimórficos
│   │   ├── BoRatController.php         ← boletim de ocorrência
│   │   └── RatAlvoController.php       ← alvos/endereços
│   └── Requests/Rat/
│       ├── BoRequest.php
│       ├── RatDadosGeraisRequest.php
│       ├── RatEnvolvidosRequest.php
│       ├── RatEnvolvidosUpdateRequest.php
│       ├── RatHistoricoRequest.php
│       ├── RatRecursosRequest.php
│       ├── RatRecursosUpdateRequest.php
│       └── RatVistoriaRequest.php
│
├── Models/
│   └── Rat/
│       ├── RatOcorrencia.php           ← entidade principal
│       ├── RatOcorrenciaRelato.php     ← pivô polimórfico
│       ├── RatRedec.php                ← regiões REDEC (lookup)
│       ├── RatVeiculo.php              ← veículos cadastrados
│       ├── Relatos/
│       │   ├── RatRelato.php           ← base abstrata (SoftDeletes + morphOne)
│       │   ├── RatRelatoDadosGerais.php
│       │   ├── RatRelatoEnvolvidos.php
│       │   ├── RatRelatoRecurso.php
│       │   └── RatRelatoVistoria.php
│       └── Recursos/
│           ├── RatRecurso.php          ← base abstrata (SoftDeletes)
│           ├── RatRecursosEmpregado.php
│           └── RatRecursosComponentesGuarnicao.php
│
├── Policies/
│   └── RatPolicy.php                   ← autorização granular por recurso
│
├── Services/Rat/
│   ├── RatOcorrenciaService.php        ← CRUD + finalização + paginação
│   ├── RatRelatoService.php            ← gerenciamento de relatos polimórficos
│   ├── RatRecursoService.php           ← recursos empregados e guarnições
│   ├── RatNovoService.php              ← extração de dados (Power BI / API)
│   ├── RatBiService.php                ← dashboards e métricas
│   ├── RatAuditService.php             ← auditoria de ações
│   └── RatTrackingService.php          ← status e linha do tempo

database/migrations/
├── 2026_02_10_100001_create_rat_ocorrencia_relatos_table.php
├── 2026_02_10_100002_create_rat_relato_recursos_table.php
├── 2026_02_10_100003_create_rat_recursos_empregados_table.php
├── 2026_02_10_100004_create_rat_recursos_componentes_guarnicao_table.php
├── 2026_02_10_131610_create_rat_bem_afetado_table.php
├── 2026_02_10_131811_create_rat_encaminhamento_table.php
├── 2026_02_10_132344_create_rat_acionado_table.php
├── 2026_02_10_132614_create_rat_patologia_table.php
├── 2026_02_10_133127_create_rat_redec_table.php
├── 2026_02_10_133300_create_rat_dados_gerais_table.php
├── 2026_02_10_133452_create_rat_relato_envolvidos_table.php
├── 2026_02_10_134052_create_rat_relato_vistoria_table.php
├── 2026_02_10_134152_create_rat_veiculos_table.php
├── 2026_03_09_200001_drop_rat_legacy_tables.php
└── 2026_03_10_000002_recreate_rat_polymorphic_tables.php

routes/
└── modules/rat.php                     ← todas as 30 rotas RAT
```

---

## 3. Banco de Dados — 15 Tabelas RAT

| # | Tabela | Propósito |
|---|--------|-----------|
| 1 | `rats` | Tabela legada (UUID + JSON) — mantida para compatibilidade |
| 2 | `rat_ocorrencias` | **Entidade principal** — protocolo/BOS |
| 3 | `rat_ocorrencia_relatos` | **Pivô polimórfico** — conecta ocorrência ➺ conteúdo |
| 4 | `rat_relato_dados_gerais` | Tipo: dados gerais da ocorrência |
| 5 | `rat_relato_envolvidos` | Tipo: envolvidos (vítimas, agentes) |
| 6 | `rat_relato_recursos` | Tipo: recursos empregados |
| 7 | `rat_recursos_empregados` | Sub: viatura/pessoal por relato de recurso |
| 8 | `rat_recursos_componentes_guarnicao` | Sub: membros da guarnição |
| 9 | `rat_relato_vistoria` | Tipo: vistoria técnica de imóvel |
| 10 | `rat_redec` | Lookup: Regiões de Defesa Civil |
| 11 | `rat_veiculos` | Catálogo: veículos cadastrados |
| 12 | `rat_bem_afetado` | Bens afetados na ocorrência |
| 13 | `rat_encaminhamento` | Encaminhamentos realizados |
| 14 | `rat_orgao_acionado` | Órgãos acionados |
| 15 | `rat_patologia` | Patologias identificadas na vistoria |

> **Todas as tabelas usam `InnoDB` (ACID) e `softDeletes` — nenhum dado é apagado fisicamente.**

---

## 4. Migrations

### Migration principal — `2026_03_10_000002_recreate_rat_polymorphic_tables.php`

Recria as 9 tabelas do núcleo polimórfico. Cada tabela tem:
- `engine = 'InnoDB'` explícito
- `charset = 'utf8mb4'` / `collation = 'utf8mb4_unicode_ci'`
- `$table->softDeletes()`
- FKs com `onDelete('set null')` — cascade via Eloquent `booted()`, não via DB

#### Tabela `rat_ocorrencias`
```sql
id                    BIGINT UNSIGNED AUTO_INCREMENT PK
numero_bos            VARCHAR(50) UNIQUE NULLABLE    -- ex: "2026-00001"
sequencial_ano        BIGINT UNSIGNED NULLABLE
status                TINYINT DEFAULT 0              -- 0=Rascunho, 1=Finalizado
prazo_edicao          TIMESTAMP NULLABLE
historico             TEXT NULLABLE
ocorrencia_origem_id  FK -> rat_ocorrencias(id) SET NULL
created_by            VARCHAR(191) NULLABLE
updated_by            VARCHAR(191) NULLABLE
created_at, updated_at, deleted_at
```

#### Tabela `rat_ocorrencia_relatos` (pivô)
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PK
ocorrencia_id   FK -> rat_ocorrencias(id) SET NULL
conteudo_id     BIGINT UNSIGNED NULLABLE
conteudo_type   VARCHAR(191) NULLABLE   -- FQCN do Model
created_by      VARCHAR(191) NULLABLE
created_at, updated_at, deleted_at
```

---

## 5. Models — Hierarquia Completa

### 5.1 `RatOcorrencia` — Entidade Principal
**Arquivo:** `app/Models/Rat/RatOcorrencia.php`

```php
class RatOcorrencia extends Model
{
    use SoftDeletes;
    protected $table = 'rat_ocorrencias';
    protected $fillable = [
        'numero_bos', 'sequencial_ano', 'status', 'prazo_edicao',
        'historico', 'ocorrencia_origem_id', 'created_by', 'updated_by',
    ];
    protected $casts = ['status' => 'integer', 'prazo_edicao' => 'datetime'];
}
```

**Relacionamentos:**
- `relatos()` → `hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id')`
- `relatosMorph()` → hasMany com `->with('conteudo')` (eager load polimórfico)
- `ocorrenciaOrigem()` → `belongsTo(self::class, 'ocorrencia_origem_id')` (BOS pai)

**Helpers:**
- `isRascunho(): bool` — `status === 0`
- `isFinalizado(): bool` — `status === 1`

**Cascade booted:**
```php
static::deleting(function (self $ocorrencia): void {
    $ocorrencia->relatos()->each->delete();
});
```

---

### 5.2 `RatOcorrenciaRelato` — Pivô Polimórfico
**Arquivo:** `app/Models/Rat/RatOcorrenciaRelato.php`

```php
class RatOcorrenciaRelato extends Model
{
    use SoftDeletes;
    protected $table = 'rat_ocorrencia_relatos';
    protected $fillable = ['ocorrencia_id', 'conteudo_id', 'conteudo_type', 'created_by'];
}
```

**Relacionamentos:**
- `ocorrencia()` → `belongsTo(RatOcorrencia::class)`
- `conteudo()` → `morphTo('conteudo')` — resolve para o tipo concreto

**Cascade booted:**
```php
static::deleting(function (self $relato): void {
    $relato->conteudo?->delete();
});
```

---

### 5.3 `RatRelato` — Classe Base Abstrata
**Arquivo:** `app/Models/Rat/Relatos/RatRelato.php`

Todos os tipos de relato estendem esta classe:
```php
abstract class RatRelato extends Model
{
    use SoftDeletes;

    public function ocorrenciaRelato(): MorphOne
    {
        return $this->morphOne(RatOcorrenciaRelato::class, 'conteudo');
    }
}
```

---

### 5.4 Tipos Concretos de Relato

#### `RatRelatoDadosGerais`
**Tabela:** `rat_relato_dados_gerais`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `data_fato` | datetime | Data/hora do fato |
| `nat_codigo` | string | Código/rat_codigo |
| `nat_cobrade_id` | integer | Código COBRADE |
| `nat_ocorrencia` | string | Tipo de ocorrência |
| `nat_nome_operacao` | string | Nome da operação |
| `local_municipio` | string | Município |
| `local_estadouf` | string | UF |
| `local_cep` / `local_logradoura_1` / `local_bairro` | string | Endereço completo |
| `uni_responsavel_*` | string | Unidade responsável |
| `com_ocorrencia_data` | datetime | Data de comunicação |

---

#### `RatRelatoEnvolvidos`
**Tabela:** `rat_relato_envolvidos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `g_tipo_pessoa` | string | Tipo da pessoa envolvida |
| `g_lesao_grau` | string | Grau da lesão |
| `g_envolvido_tipo` | string | Tipo de envolvido |
| `p_nome_completo` | string | Nome completo / razão social |
| `p_cpf` | string | CPF |
| `p_sexo` | string | Sexo |
| `p_data_nascimento` | date | Data de nascimento |
| `p_end_cep` | string | CEP |
| `g_envolvido_presenca` | boolean | Presença confirmada |
| `p_turista` | boolean | É turista? |
| `p_situacao_rua` | boolean | Situação de rua? |

---

#### `RatRelatoRecurso`
**Tabela:** `rat_relato_recursos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `seq` | integer | Sequencial do recurso |
| `recurso_tipo` | string | `viatura|pe|aereo|aquatico|outro` |
| `recurso_problemas` | boolean | Houve problemas? |
| `viatura_tipo` / `viatura_placa` | string | Identificação da viatura |
| `viatura_saida` / `viatura_chegada` | datetime | Horários |
| `viatura_km` | decimal(2) | Quilometragem |
| `viatura_quantidade` | integer | Quantidade |

**Relacionamento extra:**
- `recursosEmpregados()` → `hasMany(RatRecursosEmpregado::class, 'relato_recurso_id')`

**Cascade booted:**
```php
static::deleting(fn($r) => $r->recursosEmpregados()->each->delete());
```

---

#### `RatRelatoVistoria`
**Tabela:** `rat_relato_vistoria`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `v_solicitante_nome` / `v_solicitante_cpf` | string | Solicitante |
| `v_tipo_imovel` | string | Tipo de imóvel |
| `v_tipo_construcao` | string | Tipo de construção |
| `v_estado_conservacao` | string | Estado de conservação |
| `v_numero_pavimentos` | integer | Nº pavimentos |
| `v_numero_moradores` | integer | Nº moradores |
| `v_ha_idosos` / `v_ha_criancas` | boolean | Grupos vulneráveis |
| `v_latitude` / `v_longitude` | decimal(8) | Geolocalização |

---

### 5.5 Sub-models de Recursos

#### `RatRecurso` (Abstrata)
`app/Models/Rat/Recursos/RatRecurso.php` — base com `SoftDeletes`

#### `RatRecursosEmpregado`
**Tabela:** `rat_recursos_empregados`

Campos principais: `relato_recurso_id`, `recurso_tipo`, `viatura_placa`, `viatura_tipo`

Relacionamentos:
- `relatoRecurso()` → `belongsTo(RatRelatoRecurso::class)`
- `componentesGuarnicao()` → `hasMany(RatRecursosComponentesGuarnicao::class, 'recurso_empregado_id')`

#### `RatRecursosComponentesGuarnicao`
**Tabela:** `rat_recursos_componentes_guarnicao`

Campos principais: `recurso_empregado_id`, `relato_recurso_id`, `nome_completo`, `matricula`, `masp`, `corporacao`, `pg_cargo`, `funcao`, `is_condutor` (boolean)

---

### 5.6 Models de Referência

#### `RatRedec` — `app/Models/Rat/RatRedec.php`
**Tabela:** `rat_redec` — lookup de Regiões de Defesa Civil
Campos: `nome VARCHAR(100)`, `sigla VARCHAR(10)`
Sem SoftDeletes (tabela de referência imutável).

#### `RatVeiculo` — `app/Models/Rat/RatVeiculo.php`
**Tabela:** `rat_veiculos`
Campos: `placa` (único), `modelo`, `marca`, `ativo` (boolean)
Usa `SoftDeletes`.

---

## 6. DTOs

DTOs são classes `readonly` (PHP 8.1+) que garantem tipagem estrita entre HTTP e Services.

### `RatBoDTO`
**Arquivo:** `app/DTOs/Rat/RatBoDTO.php`

```php
readonly class RatBoDTO
{
    public function __construct(
        public ?string $numeroBos           = null,
        public ?string $historico           = null,
        public ?string $prazoEdicao         = null,
        public ?int    $ocorrenciaOrigemId  = null,
        public ?int    $status              = null,
        public ?int    $criadoPor           = null,
    ) {}

    public static function fromArray(array $data): self { ... }

    // toArray() exclui valores null -- nao sobrescreve defaults do banco
    public function toArray(): array { ... }
}
```

| Propriedade DTO | Campo no banco |
|-----------------|----------------|
| `numeroBos` | `numero_bos` |
| `historico` | `historico` |
| `prazoEdicao` | `prazo_edicao` |
| `ocorrenciaOrigemId` | `ocorrencia_origem_id` |
| `status` | `status` |
| `criadoPor` | `created_by` |

---

### `RatOcorrenciaFiltroDTO`
**Arquivo:** `app/DTOs/Rat/RatOcorrenciaFiltroDTO.php`

```php
readonly class RatOcorrenciaFiltroDTO
{
    public function __construct(
        public ?int    $status    = null,
        public ?string $numeroBos = null,
        public int     $porPagina = 15,
    ) {}

    public static function fromArray(array $data, int $porPagina = 15): self { ... }
}
```

---

### `RatDadosGeraisDTO`
**Arquivo:** `app/DTOs/Rat/RatDadosGeraisDTO.php`

Dados para criar/atualizar registros em `rat_relato_dados_gerais`.

---

## 7. Form Requests (Validação)

**Localização:** `app/Http/Requests/Rat/`

| Arquivo | Campos obrigatórios | Uso |
|---------|---------------------|-----|
| `BoRequest.php` | `numero_bos` | Criar/atualizar `RatOcorrencia` |
| `RatDadosGeraisRequest.php` | `data_fato`, `nat_cobrade_id` | Criar `RatRelatoDadosGerais` |
| `RatEnvolvidosRequest.php` | `g_tipo_pessoa`, `p_nome_completo` | Criar `RatRelatoEnvolvidos` |
| `RatEnvolvidosUpdateRequest.php` | — (todos `sometimes`) | Atualizar envolvidos |
| `RatHistoricoRequest.php` | `eventos` (array) | Atualizar histórico |
| `RatRecursosRequest.php` | `recurso_tipo` (enum) | Criar relato de recursos |
| `RatRecursosUpdateRequest.php` | — (todos `sometimes`) | Atualizar recursos |
| `RatVistoriaRequest.php` | `v_solicitante_nome` | Criar `RatRelatoVistoria` |

Regras especiais:
- `RatVistoriaRequest`: `v_latitude` com `between:-90,90`; `v_longitude` com `between:-180,180`
- `RatRecursosRequest`: `recurso_tipo` validado como `in:viatura,pe,aereo,aquatico,outro`
- `RatHistoricoRequest`: valida array `eventos.*` com `tipo`, `titulo`, `descricao`, `data`

---

## 8. Services — Camada de Negócio

### 8.1 `RatOcorrenciaService`
**Arquivo:** `app/Services/Rat/RatOcorrenciaService.php`

| Método | Assinatura | O que faz |
|--------|-----------|-----------|
| `manageOcorrencia()` | `(RatBoDTO, ?int $id): RatOcorrencia` | Cria ou atualiza em transação DB; gera `numero_bos` automático |
| `finalizar()` | `(int $id): RatOcorrencia` | Muda `status` → 1; `abort_if` já finalizado (422) |
| `paginate()` | `(RatOcorrenciaFiltroDTO): LengthAwarePaginator` | Lista com filtro de status e numero_bos |
| `findOrFail()` | `(int $id): RatOcorrencia` | Carrega com `relatosMorph` eager |

**Geração de número BOS:**
```php
private function generateNumeroBos(): string
{
    $year = date('Y');
    $seq  = RatOcorrencia::whereYear('created_at', $year)->count() + 1;
    return sprintf('%d-%05d', $year, $seq);  // ex: "2026-00001"
}
```

---

### 8.2 `RatRelatoService`
**Arquivo:** `app/Services/Rat/RatRelatoService.php`

| Método | O que faz |
|--------|-----------|
| `manageRelatos(RatOcorrencia, array)` | Substitui todos os relatos (delete + re-insert em transação) |
| `attachRelato(RatOcorrencia, string, int)` | Vincula conteúdo já criado à ocorrência via pivô |
| `detachRelato(int $relatoId)` | Soft-deleta a entrada pivô pelo ID |

---

### 8.3 `RatRecursoService`
**Arquivo:** `app/Services/Rat/RatRecursoService.php`

| Método | O que faz |
|--------|-----------|
| `createRecurso(array)` | Cria `RatRelatoRecurso` |
| `addEmpregado(int, array)` | Adiciona `RatRecursosEmpregado` ao relato de recurso |
| `addComponenteGuarnicao(int, array)` | Adiciona `RatRecursosComponentesGuarnicao` ao empregado |
| `removeEmpregado(int)` | Remove empregado **e seus componentes** em transação |

---

### 8.4 `RatNovoService`
**Arquivo:** `app/Services/Rat/RatNovoService.php`

Extração de dados normalizados para Power BI / API externa.

| Método | O que retorna |
|--------|--------------|
| `getNormalizedDataForPowerBI(Request)` | Array `{ocorrencia, dados_gerais, envolvidos, recursos}` |
| `extractDadosGerais(RatOcorrencia)` | `data_fato`, `rat_codigo`, `cobrade_id`, `municipio`, `uf`, `nome_operacao` |
| `extractEnvolvidos(RatOcorrencia)` | Array de todos os envolvidos da ocorrência |
| `extractRecursos(RatOcorrencia)` | Array de recursos com suas guarnições |

---

### 8.5 `RatBiService`
**Arquivo:** `app/Services/Rat/RatBiService.php`

| Método | O que retorna |
|--------|--------------|
| `getOcorrenciasPorStatus()` | Collection: `[{status: 'Rascunho', total: N}, ...]` |
| `getOcorrenciasPorMes()` | Contagem mensal do ano corrente |
| `getEnvolvidosPorTipo()` | Distribuição por tipo de pessoa |
| `getRecursosPorTipo()` | Distribuição por tipo de recurso |

---

### 8.6 `RatAuditService`
**Arquivo:** `app/Services/Rat/RatAuditService.php`

```php
$auditService->log('rat.criado', 'rat_ocorrencias', $ocorrencia->id, $payload);
$auditService->history($ocorrenciaId);  // histórico paginado
```

Campos gravados: `user_id`, `event`, `table_name`, `row_id`, `new_values`, `ip_address`, `user_agent`.

---

### 8.7 `RatTrackingService`
**Arquivo:** `app/Services/Rat/RatTrackingService.php`

| Método | O que faz |
|--------|-----------|
| `getTimeline(RatOcorrencia)` | Linha do tempo: criação, finalização, alterações |
| `getOcorrenciasAtivas()` | Rascunhos com prazo de edição vencendo |
| `isPrazoVencido(RatOcorrencia)` | Retorna `true` se `prazo_edicao < now()` |

---

## 9. Controllers

### 9.1 `RatController`
**Arquivo:** `app/Http/Controllers/Compdec/RatController.php`
**Injeta:** `RatOcorrenciaService`, `RatRelatoService`

| Método | HTTP | Rota | Middleware |
|--------|------|------|------------|
| `index()` | GET | `/compdec/rat` | `can:rat.protocolos.view` |
| `create()` | GET | `/compdec/rat/create` | `can:rat.protocolos.create` |
| `store()` | POST | `/compdec/rat` | `can:rat.protocolos.create` |
| `show()` | GET | `/compdec/rat/{id}` | `can:rat.protocolos.view` |
| `edit()` | GET | `/compdec/rat/{id}/edit` | `can:rat.protocolos.edit` |
| `update()` | PUT | `/compdec/rat/{id}` | `can:rat.protocolos.edit` |
| `destroy()` | DELETE | `/compdec/rat/{id}` | `can:rat.protocolos.delete` |
| `finalize()` | PATCH | `/compdec/rat/{id}/finalizar` | `can:rat.protocolos.finalize` |
| `exportRats()` | GET | `/compdec/rat/export` | `can:rat.protocolos.export` |

**Exportação CSV:** `streamDownload`, encoding UTF-8 BOM, separador `;`, filename `rat-ocorrencias-YYYY-MM-DD.csv`

---

### 9.2 `RatOcorrenciaController`
**Arquivo:** `app/Http/Controllers/Compdec/RatOcorrenciaController.php`
**Injeta:** `RatOcorrenciaService`, `RatRelatoService`

| Método | HTTP | Rota | Middleware |
|--------|------|------|------------|
| `index()` | GET | `/compdec/rat/ocorrencias` | `can:rat.protocolos.view` |
| `show()` | GET | `/compdec/rat/ocorrencias/{id}` | `can:rat.protocolos.view` |
| `store()` | POST | `/compdec/rat/ocorrencias` | `can:rat.protocolos.create` |
| `finalize()` | PATCH | `/compdec/rat/ocorrencias/{id}/finalizar` | `can:rat.protocolos.finalize` |

Renderiza views Inertia em `Compdec/Rat/Ocorrencia/`.

---

### 9.3 `BoRatController`
**Arquivo:** `app/Http/Controllers/Compdec/BoRatController.php`

| Método | HTTP | Rota | Middleware |
|--------|------|------|------------|
| `index()` | GET | `/compdec/rat/bo` | `can:rat.protocolos.view` |
| `store()` | POST | `/compdec/rat/bo` | `can:rat.protocolos.create` |

---

### 9.4 `RatAlvoController`
**Arquivo:** `app/Http/Controllers/Compdec/RatAlvoController.php`

| Método | HTTP | Rota | Middleware |
|--------|------|------|------------|
| `index()` | GET | `/compdec/rat/alvos` | `can:rat.protocolos.view` |
| `show()` | GET | `/compdec/rat/alvos/{id}` | `can:rat.protocolos.view` |

---

## 10. Rotas Registradas

**Arquivo:** `routes/modules/rat.php`

### Nova Estrutura — prefix `compdec/rat`, name `compdec.rat.`

```
GET    /compdec/rat                              compdec.rat.index
GET    /compdec/rat/create                       compdec.rat.create
POST   /compdec/rat                              compdec.rat.store
GET    /compdec/rat/export                       compdec.rat.export
GET    /compdec/rat/{id}                         compdec.rat.show
GET    /compdec/rat/{id}/edit                    compdec.rat.edit
PUT    /compdec/rat/{id}                         compdec.rat.update
DELETE /compdec/rat/{id}                         compdec.rat.destroy
PATCH  /compdec/rat/{id}/finalizar               compdec.rat.finalize
GET    /compdec/rat/bo                           compdec.rat.bo.index
POST   /compdec/rat/bo                           compdec.rat.bo.store
GET    /compdec/rat/alvos                        compdec.rat.alvos.index
GET    /compdec/rat/alvos/{id}                   compdec.rat.alvos.show
GET    /compdec/rat/ocorrencias                  compdec.rat.ocorrencias.index
GET    /compdec/rat/ocorrencias/{id}             compdec.rat.ocorrencias.show
POST   /compdec/rat/ocorrencias                  compdec.rat.ocorrencias.store
PATCH  /compdec/rat/ocorrencias/{id}/finalizar   compdec.rat.ocorrencias.finalize
```

### Estrutura Legada — prefix `rat`, name `rat.`

```
GET    /rat                         rat.index
GET    /rat/create                  rat.create
POST   /rat                         rat.store
GET    /rat/export                  rat.export
GET    /rat/{id}                    rat.show
GET    /rat/{id}/json               rat.show.json
GET    /rat/{id}/edit               rat.edit
PUT    /rat/{id}                    rat.update
PATCH  /rat/{id}/draft              rat.draft
PATCH  /rat/{id}/finalize           rat.finalize
DELETE /rat/{id}                    rat.destroy
POST   /rat/{id}/attachments             rat.attachments.store
DELETE /rat/{id}/attachments/{id}        rat.attachments.destroy
```

**Total: 30 rotas RAT** — todas com middleware `can:` middleware

---

## 11. Permissionamento e Policy

### Padrão de Slug — `modulo.recurso.acao`

```
rat.protocolos.view
rat.protocolos.create
rat.protocolos.edit
rat.protocolos.delete
rat.protocolos.finalize
rat.protocolos.export
```

O modelo `Permission` (`app/Models/Permission.php`), que estende Spatie, possui campos `slug`, `module` e `group`.

### `RatPolicy`
**Arquivo:** `app/Policies/RatPolicy.php`

Estende `BasePolicy` — super-admin recebe `true` automaticamente no `before()`.

| Método | Regra de negócio |
|--------|-----------------|
| `viewAny($user)` | Requer `rat.protocolos.view` |
| `view($user, $ocorrencia)` | Requer `rat.protocolos.view` |
| `create($user)` | Requer `rat.protocolos.create` |
| `update($user, $ocorrencia)` | **Criador pode editar seu próprio rascunho sem permissão extra.** Outros precisam de `rat.protocolos.edit` |
| `delete($user, $ocorrencia)` | Requer `rat.protocolos.delete` |
| `finalize($user, $ocorrencia)` | Requer `rat.protocolos.finalize`; rejeita com mensagem se já finalizado |
| `export($user)` | Requer `rat.protocolos.export` |

**Registrada em** `app/Providers/AuthServiceProvider.php`:
```php
\App\Models\Rat\RatOcorrencia::class => \App\Policies\RatPolicy::class,
```

---

## 12. Conexões de Banco de Dados

**Arquivo:** `config/database.php`

| Conexão | Propósito | Variáveis de ambiente |
|---------|-----------|-----------------------|
| `mysql` (padrão) | Banco principal da aplicação | `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `legacy` | Integração/migração do banco legado | `DB_LEGACY_HOST`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME` |
| `carga` | Queries analíticas/BI/ETL — réplica de leitura, `strict: false` | `DB_CARGA_HOST`, `DB_CARGA_DATABASE`, `DB_CARGA_USERNAME` |

**Usar a conexão `carga`:**
```php
DB::connection('carga')->table('rat_ocorrencias')->...;
```

---

## 13. Fluxo Completo de uma Requisição

### Criar nova ocorrência — `POST /compdec/rat`

```
1. HTTP  POST /compdec/rat
         |
2. Gate  -> can:rat.protocolos.create (Spatie Permission)
         |
3. RatController::store(BoRequest $request)
         |   BoRequest::rules() valida numero_bos, historico, prazo_edicao...
         |
4. RatBoDTO::fromArray($request->validated())
         |   array -> objeto readonly tipado
         |
5. RatOcorrenciaService::manageOcorrencia(RatBoDTO)
         |   DB::transaction() {
         |     gera numero_bos "2026-00001" se vazio
         |     status = 0 (Rascunho)
         |     created_by = auth()->id()
         |     RatOcorrencia::create($data)
         |   }
         |
6. RatRelatoService::manageRelatos($ocorrencia, $relatos)
         |   (se payload contiver relatos)
         |   delete relatos existentes
         |   RatOcorrenciaRelato::create([conteudo_id, conteudo_type])
         |
7. redirect()->route('compdec.rat.show', $id)
         +-- with('success', 'Ocorrencia RAT criada com sucesso!')
```

### Soft Delete com Cascade — `DELETE /compdec/rat/{id}`

```
1. RatPolicy::delete($user, $ocorrencia) -> verifica rat.protocolos.delete
         |
2. RatController::destroy($ocorrencia)
         |   $ocorrencia->delete()
         |
3. RatOcorrencia::booted() -> evento deleting
         |   $ocorrencia->relatos()->each->delete()
         |
4. Para cada RatOcorrenciaRelato:
         |   booted() -> evento deleting -> $relato->conteudo?->delete()
         |
5. Se conteudo e RatRelatoRecurso:
         |   booted() -> evento deleting -> $recurso->recursosEmpregados()->each->delete()
         |
6. Resultado: deleted_at preenchido em TODA a cadeia.
             Dados preservados fisicamente no banco.
```

---

## 14. Diagrama de Relacionamentos

```
rat_ocorrencias                        <- RatOcorrencia
  |  id, numero_bos, status, prazo_edicao, historico
  |  ocorrencia_origem_id (auto-FK), created_by
  |  SoftDeletes + InnoDB
  |
  +--[hasMany] rat_ocorrencia_relatos   <- RatOcorrenciaRelato
       |  id, ocorrencia_id, conteudo_id, conteudo_type
       |  SoftDeletes
       |
       +--[morphTo] rat_relato_dados_gerais  <- RatRelatoDadosGerais
       |   data_fato, nat_cobrade_id, local_municipio, local_estadouf
       |
       +--[morphTo] rat_relato_envolvidos    <- RatRelatoEnvolvidos
       |   g_tipo_pessoa, p_nome_completo, p_cpf, p_data_nascimento
       |
       +--[morphTo] rat_relato_recursos      <- RatRelatoRecurso
       |   seq, recurso_tipo, viatura_placa
       |   |
       |   +--[hasMany] rat_recursos_empregados  <- RatRecursosEmpregado
       |        relato_recurso_id, recurso_tipo, viatura_placa
       |        |
       |        +--[hasMany] rat_recursos_componentes_guarnicao
       |             recurso_empregado_id, nome_completo
       |             matricula, masp, corporacao, is_condutor
       |
       +--[morphTo] rat_relato_vistoria       <- RatRelatoVistoria
            v_solicitante_nome, v_tipo_imovel
            v_estado_conservacao, v_latitude, v_longitude

rat_redec      <- RatRedec     (nome, sigla -- 14 REDECs de MG)
rat_veiculos   <- RatVeiculo   (placa, modelo, marca, ativo)
rat_bem_afetado / rat_encaminhamento / rat_orgao_acionado / rat_patologia
```

---

## Resumo Executivo

| Componente | Contagem | Detalhe |
|------------|----------|---------|
| Tabelas RAT | **15** | InnoDB + SoftDeletes em todas |
| Models | **12** | Incluindo bases abstratas |
| Services | **7** | Um por responsabilidade |
| Controllers | **4** | Namespace `Compdec` |
| Form Requests | **8** | Validação na borda HTTP |
| DTOs | **3** | Tipagem estrita HTTP -> Service |
| Rotas | **30** | Todas com `can:` middleware |
| Policy | **1** | `RatPolicy` — 6 métodos |
| Conexões DB | **3** | mysql, legacy, carga |

---

*Documentado em 11/03/2026 — Módulo RAT, Barbara Costa*
