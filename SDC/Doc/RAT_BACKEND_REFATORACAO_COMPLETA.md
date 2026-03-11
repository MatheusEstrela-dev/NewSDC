**Autora:** Barbara Costa  

---

# Fase 2 — Nova Arquitetura Polimórfica 

**Data:** 10/03/2026  
**Status:** ✅ 35/35 arquivos implementados e funcionais  
**Rotas totais registradas:** 44 (web + API)  
**Validação:** `php artisan route:cache` — sucesso no container `newsdc_app`

---

## Arquitetura da Nova Estrutura

```
Requisição HTTP
      │
  Controller  ──→  FormRequest (validação de entrada)
      │
   Service     ──→  Model / Repositório
      │
  RatOcorrencia  (entidade principal — tabela rat_ocorrencias)
      │
  RatOcorrenciaRelato  (pivô polimórfico)
      ├── RatRelatoDadosGerais       — dados gerais do fato
      ├── RatRelatoEnvolvidos        — vítimas / agentes
      ├── RatRelatoRecurso           — recursos empregados
      │       └── RatRecursosEmpregado
      │               └── RatRecursosComponentesGuarnicao
      └── RatRelatoVistoria          — inspeção técnica
```
---

## Controllers Criados — 6/6

### `Compdec/RatController.php`
Controller principal CRUD da nova estrutura. Depende de `RatOcorrenciaService`.  
Usa `BoRequest` para validar `store()` e `update()` (campos reais de `rat_ocorrencias`).

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET `/compdec/rat` | Lista paginada de ocorrências |
| `create()` | GET `/compdec/rat/create` | Formulário de criação (Inertia) |
| `store()` | POST `/compdec/rat` | Persiste nova ocorrência |
| `show()` | GET `/compdec/rat/{id}` | Exibe ocorrência (Inertia) |
| `edit()` | GET `/compdec/rat/{id}/edit` | Formulário de edição (Inertia) |
| `update()` | PUT `/compdec/rat/{id}` | Atualiza ocorrência |
| `destroy()` | DELETE `/compdec/rat/{id}` | Remove ocorrência |
| `exportarOcorrencias()` | GET `/compdec/rat/export` | Exporta listagem |

### `Compdec/BoRatController.php`
Boletim de Ocorrência (BO) vinculado às ocorrências RAT.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/bo` |
| `store()` | POST `/compdec/rat/bo` |

### `Compdec/RatAlvoController.php`
Alvos associados às ocorrências.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/alvos` |
| `show()` | GET `/compdec/rat/alvos/{id}` |

### `Compdec/RatOcorrenciaController.php`
Coordena ocorrências com seus relatos polimórficos. Depende de `RatOcorrenciaService` e `RatRelatoService`.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/ocorrencias` |
| `show()` | GET `/compdec/rat/ocorrencias/{id}` |
| `store()` | POST `/compdec/rat/ocorrencias` |
| `finalize()` | PATCH `/compdec/rat/ocorrencias/{id}/finalizar` |

### `Api/RatAuditController.php`
Endpoints da API para trilha de auditoria.

| Método | Rota |
|--------|------|
| `index()` | GET `/api/v1/rat-audit` |
| `show()` | GET `/api/v1/rat-audit/{id}` |

### `Api/RatNovoController.php`
Endpoints da API para integração Power BI.

| Método | Rota |
|--------|------|
| `index()` | GET `/api/v1/rat-novo` |
| `show()` | GET `/api/v1/rat-novo/{id}` |
| `powerBiData()` | GET `/api/v1/rat-novo/{id}/power-bi` |

---

## Services Criados — 7/7

Localização: `app/Services/Rat/`

| Serviço | Principais Métodos | Responsabilidade |
|---------|-------------------|------------------|
| `RatOcorrenciaService` | `manageOcorrencia()`, `finalizar()`, `paginate()`, `findOrFail()` | Regras de negócio da ocorrência principal |
| `RatRelatoService` | `manageRelatos()`, `attachRelato()`, `detachRelato()` | Gerencia relatos polimórficos vinculados |
| `RatNovoService` | `getNormalizedDataForPowerBI()`, `extractDadosGerais()`, `extractEnvolvidos()`, `extractRecursos()` | Normalização de dados para Power BI |
| `RatBiService` | `getOcorrenciasPorStatus()`, `getOcorrenciasPorMes()`, `getEnvolvidosPorTipo()`, `getRecursosPorTipo()` | Agregações para dashboard gerencial |
| `RatRecursoService` | `createRecurso()`, `addEmpregado()`, `addComponenteGuarnicao()`, `removeEmpregado()` | Gestão de recursos empregados e guarnição |
| `RatAuditService` | `log()`, `history()` | Trilha de auditoria (`table_name` / `row_id`) |
| `RatTrackingService` | `getTimeline()`, `getOcorrenciasAtivas()`, `isPrazoVencido()` | Monitoramento e alertas de prazo |

---

## Models Criados — 12/12

### Núcleo

**`app/Models/Rat/RatOcorrencia.php`** — Entidade principal (`rat_ocorrencias`)

| Campo | Descrição |
|-------|-----------|
| `numero_bos` | Número do Boletim de Ocorrência |
| `sequencial_ano` | Sequencial anual |
| `status` | 0=Rascunho / 1=Finalizado |
| `prazo_edicao` | Prazo limite de edição |
| `historico` | Observações gerais |
| `ocorrencia_origem_id` | Auto-referência para divisões de BO |
| `created_by` / `updated_by` | Rastreamento de usuário |

Relacionamentos: `relatos()`, `relatosMorph()`, `ocorrenciaOrigem()`

**`app/Models/Rat/RatOcorrenciaRelato.php`** — Pivô polimórfico  
Campos: `ocorrencia_id`, `conteudo_type`, `conteudo_id`  
Relacionamentos: `ocorrencia()` → BelongsTo, `conteudo()` → morphTo

**`app/Models/Rat/RatRedec.php`** — Tabela de referência REDEC  
**`app/Models/Rat/RatVeiculo.php`** — Veículos (soft delete habilitado)

### Relatos — Conteúdo Polimórfico (`app/Models/Rat/Relatos/`)

| Model | Campos Principais |
|-------|-------------------|
| `RatRelato.php` (base abstrata) | `ocorrenciaRelato()` → morphOne |
| `RatRelatoDadosGerais.php` | `data_fato`, `nat_cobrade_id`, `nat_nome_operacao`, `local_municipio`, `local_estadouf` |
| `RatRelatoEnvolvidos.php` | `g_tipo_pessoa`, `g_lesao_grau`, `p_nome_completo`, `p_cpf`, `p_data_nascimento`, `p_sexo` |
| `RatRelatoRecurso.php` | `recurso_tipo`, `viatura_placa`, `viatura_saida`, `viatura_chegada`; → `recursosEmpregados()` |
| `RatRelatoVistoria.php` | `v_solicitante_nome`, `v_tipo_imovel`, `v_estado_conservacao`, `v_latitude`, `v_longitude` |

### Recursos — Hierarquia Aninhada (`app/Models/Rat/Recursos/`)

| Model | Relacionamentos |
|-------|----------------|
| `RatRecurso.php` (base abstrata) | Soft delete habilitado |
| `RatRecursosEmpregado.php` | `relatoRecurso()` → BelongsTo; `componentesGuarnicao()` → HasMany |
| `RatRecursosComponentesGuarnicao.php` | `recursoEmpregado()` → BelongsTo; campos: `nome_completo`, `matricula` |

---

## Requests Criados — 8/8

Localização: `app/Http/Requests/Rat/`

| Request | Campos Validados |
|---------|-----------------|
| `BoRequest.php` | `numero_bos` (required), `historico`, `prazo_edicao`, `ocorrencia_origem_id` |
| `RatDadosGeraisRequest.php` | `data_fato` (required), `nat_cobrade_id` (required), localização, endereço |
| `RatEnvolvidosRequest.php` | `g_tipo_pessoa` (required), `p_nome_completo` (required), CPF, nascimento, sexo, endereço |
| `RatEnvolvidosUpdateRequest.php` | Todos os campos `sometimes` (atualização parcial) |
| `RatHistoricoRequest.php` | Array `eventos.*`: tipo, título, descrição, data; requer permissão `rat.protocolos.edit` |
| `RatRecursosRequest.php` | `recurso_tipo` (viatura\|pe\|aereo\|aquatico\|outro), viatura, guarnição aninhada |
| `RatRecursosUpdateRequest.php` | Todos os campos `sometimes` (atualização parcial) |
| `RatVistoriaRequest.php` | `v_solicitante_nome` (required), tipo imóvel, moradores, lat (`between:-90,90`), lon (`between:-180,180`) |

---

## Correções de Comunicação Front ↔ Back ↔ Banco

| # | Problema | Arquivo | Correção |
|---|----------|---------|---------|
| 1 | `RatController` usava `RatDadosGeraisRequest` (campos de relato) em vez dos campos reais de `rat_ocorrencias` | `Compdec/RatController.php` | Trocado para `BoRequest` em `store()` e `update()` |
| 2 | `powerBiData()` chamava `findOrFail()` 3× no mesmo ID | `Api/RatNovoController.php` | Unificado em uma única query reutilizando `$ocorrencia` |
| 3 | Vue Create/Edit usavam `data_hora_fato` e `descricao` (inexistentes em `rat_ocorrencias`) | `Pages/Compdec/Rat/Create.vue` e `Edit.vue` | Corrigidos para `prazo_edicao` e `historico` |
| 4 | `RatAuditService` usava `auditable_type/auditable_id` (colunas inexistentes) | `Services/Rat/RatAuditService.php` | Corrigido para `table_name/row_id` |
| 5 | `RatHistoricoRequest` validava string em vez de array de eventos | `Requests/Rat/RatHistoricoRequest.php` | Corrigido para validação de array `eventos.*` |
| 6 | Composable chamava rota `rat.sync` inexistente | `Composables/rat/useRat.js` | Corrigido para `rat.store` / `rat.update` |

---

### Composables renomeados

| Arquivo | Antes → Depois |
|---------|---------------|
| `Composables/useRat.js` | `historyEvents` → `historico`, `saveRat` → `salvarRat`, `saveDraft` → `salvarRascunho`, `cancelRat` → `cancelarRat` |
| `Composables/rat/useRat.js` | + `addRecurso` → `adicionarRecurso`, `removeRecurso` → `removerRecurso`, `addEnvolvido` → `adicionarEnvolvido`, `removeEnvolvido` → `removerEnvolvido`, `saveVistoria` → `salvarVistoria`, `addObservation` → `adicionarObservacao`, `addAnexo` → `adicionarAnexo`, `removeAnexo` → `removerAnexo` |
| `Composables/rat/useRatFilters.js` | `hasActiveFilters` → `temFiltrosAtivos`, `updateFilter` → `atualizarFiltro`, `resetFilters` → `limparFiltros`, `applyFilters` → `aplicarFiltros`, `clearFilters` → `limparTodosFiltros` |
| `Composables/rat/useRatStatistics.js` | `formattedStatistics` → `estatisticasFormatadas`, `updateStatistics` → `atualizarEstatisticas` |
| `Composables/rat/useCollapsible.js` | `isExpanded` → `estaExpandido`, `toggle` → `alternar`, `expand` → `expandir`, `collapse` → `recolher`, `loadState` → `carregarEstado`, `saveState` → `salvarEstado`, `expandAll` → `expandirTodos`, `collapseAll` → `recolherTodos`, `toggleAll` → `alternarTodos` |

### Callers atualizados

- `Pages/Rat.vue` — prop `historico`, estado `historicoEstado`, chamadas `salvarRat/salvarRascunho/cancelarRat`, computed `historico`, template `:events="historico"`
- `Components/Rat/Sections/RatCollapsibleSection.vue` — `estaExpandido`, `alternar` em script e template

---

## Rotas da Nova Estrutura (acréscimo ao total de 44)

### Web — Nova Estrutura (`/compdec/rat`)
```
GET|HEAD   compdec/rat                                compdec.rat.index
POST       compdec/rat                                compdec.rat.store
GET|HEAD   compdec/rat/create                         compdec.rat.create
GET|HEAD   compdec/rat/export                         compdec.rat.export
GET|HEAD   compdec/rat/{id}                           compdec.rat.show
GET|HEAD   compdec/rat/{id}/edit                      compdec.rat.edit
PUT        compdec/rat/{id}                           compdec.rat.update
DELETE     compdec/rat/{id}                           compdec.rat.destroy
GET|HEAD   compdec/rat/bo                             compdec.rat.bo.index
POST       compdec/rat/bo                             compdec.rat.bo.store
GET|HEAD   compdec/rat/alvos                          compdec.rat.alvos.index
GET|HEAD   compdec/rat/alvos/{id}                     compdec.rat.alvos.show
GET|HEAD   compdec/rat/ocorrencias                    compdec.rat.ocorrencias.index
POST       compdec/rat/ocorrencias                    compdec.rat.ocorrencias.store
GET|HEAD   compdec/rat/ocorrencias/{id}               compdec.rat.ocorrencias.show
PATCH      compdec/rat/ocorrencias/{id}/finalizar     compdec.rat.ocorrencias.finalizar
```

### API v1 — Nova Estrutura (`/api/v1`)
```
GET|HEAD   api/v1/rat-novo                            api.v1.rat-novo.index
GET|HEAD   api/v1/rat-novo/{id}                       api.v1.rat-novo.show
GET|HEAD   api/v1/rat-novo/{id}/power-bi              api.v1.rat-novo.power-bi
GET|HEAD   api/v1/rat-audit                           api.v1.rat-audit.index
GET|HEAD   api/v1/rat-audit/{id}                      api.v1.rat-audit.show
```

---

## Resumo Quantitativo — Fase 2

| Categoria | Quantidade |
|-----------|-----------|
| Controllers novos criados | 6 |
| Services novos criados | 7 |
| Models novos criados | 12 |
| Requests novos criados | 8 |
| Composables JS renomeados para PT | 5 |
| Páginas Vue criadas (Compdec/Rat) | 9 |
| Bugs de comunicação corrigidos | 6 |
| Docblocks PHP traduzidos | 15 arquivos |
| Rotas totais ativas | 44 |

---

*Fase 2 documentada em 10/03/2026 — Módulo RAT, Barbara Costa*

---

---

# Fase 3 — Recriação das Tabelas, Seeders e DTOs

**Data:** 10/03/2026  
**Status:** ✅ Banco restaurado · Seeders populados · Fluxo Controller → DTO → Service → Model → DB completo  
**Validação:** `php artisan optimize` — `config ✓ events ✓ routes ✓ views ✓`

---

## Problema Crítico Identificado e Resolvido

A migration `2026_03_09_200001_drop_rat_legacy_tables` (Fase 1, batch 4) havia listado como "legadas" 9 tabelas que **pertencem à nova arquitetura polimórfica** da Fase 2. Todas foram deletadas do banco, tornando todos os endpoints `Compdec/Rat` inoperantes (HTTP 500 — table not found).

**Tabelas que foram incorretamente deletadas:**

| Tabela | Impacto |
|--------|---------|
| `rat_ocorrencias` | Entidade principal — `RatOcorrencia` model |
| `rat_ocorrencia_relatos` | Pivô polimórfico — `RatOcorrenciaRelato` model |
| `rat_relato_recursos` | Recursos do relato — `RatRelatoRecurso` model |
| `rat_recursos_empregados` | Viaturas/pés — `RatRecursosEmpregado` model |
| `rat_recursos_componentes_guarnicao` | Guarnição — `RatRecursosComponentesGuarnicao` model |
| `rat_relato_envolvidos` | Pessoas envolvidas — `RatRelatoEnvolvidos` model |
| `rat_relato_vistoria` | Inspeção técnica — `RatRelatoVistoria` model |
| `rat_veiculos` | Cadastro de veículos — `RatVeiculo` model |
| `rat_redec` | Referência REDEC — `RatRedec` model |

**Tabelas que sobreviveram (não estavam na lista de drop):**

| Tabela | Status |
|--------|--------|
| `rats` | ✅ Intacta — tabela legada Fase 1 |
| `rat_relato_dados_gerais` | ✅ Intacta — nome diferente de `rat_dados_gerais` |

---

## 1. Migration de Recriação

**Arquivo criado:** `database/migrations/2026_03_10_000002_recreate_rat_polymorphic_tables.php`  
**Batch:** 5 · **Duração:** 2s · **Status:** ✅ DONE

Recria todas as 9 tabelas na **ordem correta de dependência de FK**, usando `if (! Schema::hasTable(...))` para ser idempotente:

1. `rat_redec` — tabela de referência (sem FKs externas)
2. `rat_ocorrencias` — entidade principal; FK auto-referenciada `ocorrencia_origem_id` → `rat_ocorrencias.id` ON DELETE SET NULL
3. `rat_ocorrencia_relatos` — pivô polimórfico; FK `ocorrencia_id` → `rat_ocorrencias.id` ON DELETE SET NULL
4. `rat_relato_recursos` — recursos por relato (sem FKs de entrada)
5. `rat_recursos_empregados` — FK `relato_recurso_id` → `rat_relato_recursos.id` ON DELETE CASCADE
6. `rat_recursos_componentes_guarnicao` — referencia `recurso_empregado_id` e `relato_recurso_id`
7. `rat_relato_envolvidos` — pessoas; soft delete; campo `created_by` (nullable para compatibilidade)
8. `rat_relato_vistoria` — 117 colunas (patologias, bens, órgãos, encaminhamentos, geo)
9. `rat_veiculos` — único por placa; soft delete

**Resultado confirmado (colunas por tabela):**

| Tabela | Colunas |
|--------|---------|
| `rat_ocorrencias` | 12 |
| `rat_ocorrencia_relatos` | 8 |
| `rat_relato_recursos` | 28 |
| `rat_recursos_empregados` | 16 |
| `rat_recursos_componentes_guarnicao` | 17 |
| `rat_relato_envolvidos` | 54 |
| `rat_relato_vistoria` | 117 |
| `rat_veiculos` | 8 |
| `rat_redec` | 5 |

**Migration inválida deletada:** `2026_03_10_000001_add_fk_rat_ocorrencia_relatos_ocorrencia_id.php`  
(Criada anteriormente e falhada porque a tabela não existia — removida do repositório.)

---

## 2. Soft Delete em Cascata — Booted Events

Adicionado `booted()` em 3 models para garantir o comportamento conforme o whiteboard ("banco não vai excluir nada — o front exclui meio que camuflado mas os dados permanecem"):

### `RatOcorrencia` → cascateia para relatos
```php
protected static function booted(): void
{
    static::deleting(function (self $ocorrencia): void {
        $ocorrencia->relatos()->each->delete();
    });
}
```

### `RatOcorrenciaRelato` → cascateia para conteúdo polimórfico
```php
protected static function booted(): void
{
    static::deleting(function (self $relato): void {
        $relato->conteudo?->delete();
    });
}
```

### `RatRelatoRecurso` → cascateia para recursos empregados
```php
protected static function booted(): void
{
    parent::booted();
    static::deleting(function (self $recurso): void {
        $recurso->recursosEmpregados()->each->delete();
    });
}
```

**Docblock `destroy()` corrigido** em `Compdec/RatController.php`:
- Antes: `/** Remove a ocorrência permanentemente. */`
- Depois: `/** Oculta a ocorrência via soft delete (dados preservados no banco). Cascateia o soft delete para todos os relatos e conteúdos relacionados. */`
- Flash message: `'Ocorrência removida com sucesso!'` → `'Ocorrência ocultada com sucesso!'`

**Models com SoftDeletes ativo:** `RatOcorrencia`, `RatOcorrenciaRelato`, `RatRelatoRecurso` (base), `RatVeiculo`, `RatRedec`

---

## 3. Seeders

### `RatRedecSeeder.php` — **NOVO**
**Arquivo:** `database/seeders/RatRedecSeeder.php`

Popula `rat_redec` com as **14 Regiões de Defesa Civil de Minas Gerais**. Idempotente via `updateOrInsert` na `sigla`.

| Sigla | Região |
|-------|--------|
| 1ª REDEC | Metropolitana de Belo Horizonte |
| 2ª REDEC | Vale do Paraopeba |
| 3ª REDEC | Campo das Vertentes |
| 4ª REDEC | Zona da Mata |
| 5ª REDEC | Triângulo Norte |
| 6ª REDEC | Triângulo Sul |
| 7ª REDEC | Norte de Minas |
| 8ª REDEC | Vale do Rio Doce |
| 9ª REDEC | Mucuri |
| 10ª REDEC | Oeste de Minas |
| 11ª REDEC | Sul de Minas |
| 12ª REDEC | Circuito das Águas |
| 13ª REDEC | Serrana do Sul |
| 14ª REDEC | Jequitinhonha |

**Executado:** `php artisan db:seed --class=RatRedecSeeder` → `14 REDECs de Minas Gerais inseridas.` ✅

### `DatabaseSeeder.php` — **ATUALIZADO**
Adicionada chamada ao `RatRedecSeeder` após o `RatMockSeeder`, com guarda `Schema::hasTable('rat_redec')`:

```php
// 6b. REDECs de Minas Gerais (tabela de referência rat_redec)
if (\Illuminate\Support\Facades\Schema::hasTable('rat_redec')) {
    $this->call(RatRedecSeeder::class);
} else {
    $this->command->warn('Tabela "rat_redec" não encontrada - RatRedecSeeder pulado.');
}
```

---

## 4. DTOs — Fluxo Controller → DTO → Service → Model → DB

Implementado o padrão completo do whiteboard "Padrão Ouro". Os DTOs ficam em `app/DTOs/Rat/`, seguindo o padrão `readonly class` já adotado pelo `RatFilterDTO` legado.

### `RatBoDTO`
**Arquivo:** `app/DTOs/Rat/RatBoDTO.php`

Representa os dados de criação/atualização de um Boletim de Ocorrência.

| Campo DTO | Campo DB | Tipo |
|-----------|----------|------|
| `numeroBos` | `numero_bos` | `?string` |
| `historico` | `historico` | `?string` |
| `prazoEdicao` | `prazo_edicao` | `?string` |
| `ocorrenciaOrigemId` | `ocorrencia_origem_id` | `?int` |
| `status` | `status` | `?int` |
| `criadoPor` | `created_by` | `?int` |

```php
// Uso no controller:
$ocorrencia = $this->service->manageOcorrencia(
    RatBoDTO::fromArray($request->validated())
);
```

### `RatOcorrenciaFiltroDTO`
**Arquivo:** `app/DTOs/Rat/RatOcorrenciaFiltroDTO.php`

Encapsula critérios de listagem/paginação.

| Campo DTO | Campo HTTP | Tipo | Default |
|-----------|------------|------|---------|
| `status` | `status` | `?int` | `null` |
| `numeroBos` | `numero_bos` | `?string` | `null` |
| `porPagina` | `per_page` | `int` | `15` |

```php
// Uso no controller:
$filtro = RatOcorrenciaFiltroDTO::fromArray(request()->only(['status', 'numero_bos']));
$ocorrencias = $this->service->paginate($filtro);
```

### `RatDadosGeraisDTO`
**Arquivo:** `app/DTOs/Rat/RatDadosGeraisDTO.php`

22 campos da tabela `rat_relato_dados_gerais`, mapeados pelos campos de `RatDadosGeraisRequest`. `toArray()` exclui campos nulos para não sobrescrever defaults do banco.

---

## 5. Service e Controllers Atualizados

### `RatOcorrenciaService` — assinaturas com DTOs

| Método (antes) | Método (depois) |
|----------------|-----------------|
| `manageOcorrencia(array $data, ?int $id)` | `manageOcorrencia(RatBoDTO $dto, ?int $id)` |
| `paginate(array $filters, int $perPage)` | `paginate(RatOcorrenciaFiltroDTO $filtro)` |

A lógica interna permanece idêntica — apenas a assinatura pública é tipada.

### Controllers atualizados (4)

| Controller | Mudanças |
|------------|---------|
| `Compdec/RatController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` e `update()` usam `RatBoDTO`; `exportRats()` usa `RatOcorrenciaFiltroDTO` com `porPagina=9999` |
| `Compdec/BoRatController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` usa `RatBoDTO` |
| `Compdec/RatOcorrenciaController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` usa `RatBoDTO` + `RatDadosGeraisDTO` |
| `Compdec/RatAlvoController` | `index()` usa `RatOcorrenciaFiltroDTO` com `status=0` |

---

## 6. Fluxo Completo Atualizado — Nova Arquitetura

```
[Usuário faz requisição HTTP]
        ↓
[FormRequest → validated()]
  BoRequest / RatDadosGeraisRequest / RatVistoriaRequest ...
        ↓ fromArray($request->validated())
[DTO → tipagem estrita]
  RatBoDTO / RatOcorrenciaFiltroDTO / RatDadosGeraisDTO
        ↓ dto passado ao Service
[RatOcorrenciaService / RatRelatoService]
  DB::transaction() + dto->toArray() → array filtrado sem nulos
        ↓
[Model Eloquent — RatOcorrencia, RatOcorrenciaRelato ...]
  create($data) / update($data)
        ↓
[MySQL — tabelas rat_ocorrencias, rat_ocorrencia_relatos ...]
  Soft delete preserva dados; cascade via booted() events
```

---

## 7. Resumo Quantitativo — Fase 3

| Categoria | Quantidade |
|-----------|-----------|
| Migration de recriação criada e executada | 1 |
| Tabelas polimórficas recriadas no banco | 9 |
| Migration inválida deletada | 1 |
| Models com cascade soft delete adicionado | 3 |
| Seeder novo criado (`RatRedecSeeder`) | 1 |
| REDECs inseridas em `rat_redec` | 14 |
| `DatabaseSeeder` atualizado | 1 |
| DTOs novos criados (`app/DTOs/Rat/`) | 3 |
| Service atualizado com assinaturas DTO | 1 |
| Controllers atualizados para usar DTOs | 4 |
| Cache final: `php artisan optimize` | ✅ |

---

*Fase 3 documentada em 10/03/2026 — Módulo RAT, Barbara Costa*
