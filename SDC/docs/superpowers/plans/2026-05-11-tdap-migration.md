# TDAP — Plano de Migração do Legado para o Novo SDC

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) ou superpowers:executing-plans para implementar este plano fase-a-fase. Steps usam checkbox (`- [ ]`) para tracking.

## Contexto

O módulo **TDAP — Transporte e Distribuição de Água Potável** existe no sistema legado (`C:\Users\x24679188\Documents\Github\sdc`) como um conjunto de 9 controllers + 11 models + 37 views Blade que gerenciam o ciclo completo de contratação e execução do fornecimento emergencial de água potável por prestadores de serviço em municípios afetados. O novo SDC (`C:\Users\x24679188\Documents\Github\NewSDC`) adota arquitetura modular Inertia + Vue 3 e ainda não tem esse módulo portado — existe apenas um esqueleto vazio com escopo de "estoque" (`Product`/`Recebimento`/`Movimentacao`) que precisará ser realocado.

**Objetivo:** portar 1:1 a regra de negócio do TDAP legado para `app/Modules/Tdap/` no novo sistema, em fases incrementais que entregam valor isoladamente (cadastros → contratos → cronogramas → vistorias → auditoria), cada fase cobrindo DB + Backend + Frontend de ponta a ponta.

**Achados de descoberta:**

- O esqueleto `app/Modules/Tdap/` em NewSDC (`Product.php`, `Recebimento.php`, `Movimentacao.php`, permissões `tdap.products.*`/`tdap.recebimentos.*`) é POC de estoque e **não corresponde** ao TDAP legado. Será **renomeado** para `Almoxarifado` antes do início da migração (Fase 0).
- `resources/views/inventario/` no legado é módulo de **empréstimo de equipamentos** (categorias, equipamentos, empréstimos), não almoxarifado. Fora do escopo desta migração.
- `app/Http/Controllers/Estoque/` e `app/Http/Controllers/Inventario/` legados também ficam fora do escopo.

**Tech Stack alvo:**

- **Backend:** Laravel 11 + PHP 8.3 (FrankenPHP), arquitetura **DDD + Service Layer SOLID** (espelha `app/Modules/Rat/`).
- **Banco:** **PostgreSQL 18 + PostGIS 3.6** (`postgis/postgis:18-3.6-alpine` já no docker-compose). Tipos `jsonb`, `geography(Point)`, `CHECK constraints` para ENUMs, índices `GIN` para JSON.
- **Frontend:** Inertia.js + Vue 3 Composition API, **Atomic Design completo** (`Atoms → Molecules → Sections → Organisms → Templates → Pages`), Tailwind 3.2, TanStack Vue Query 5, Ziggy 2.6, vue3-apexcharts, vuedraggable.
- **Auth/Perm:** Spatie Permissions com hierarquia em `config/permissions.php`.
- **Workflow & Eventos (Fase 6):** **Event-Driven Monolith**. `spatie/laravel-model-states` para máquina de estados declarativa, **Transactional Outbox Pattern** (`outbox_events` + worker assíncrono via Horizon/queue), `processed_events` para idempotência de listeners cross-module, Domain Events imutáveis (PHP 8 `readonly`), Sagas para fluxos multi-estado, Read-Model Projections para dashboards.

**Arquitetura alvo (referência canônica: `app/Modules/Rat/`):**

```
app/Modules/Tdap/
├── Controllers/                      ← Controllers finos (orquestracao + render Inertia)
├── Domain/
│   └── Repositories/                 ← Interfaces (contratos) — Dependency Inversion (D do SOLID)
│       ├── PrestadorRepositoryInterface.php
│       ├── CaminhaoRepositoryInterface.php
│       ├── AtaRepositoryInterface.php
│       ├── LoteRepositoryInterface.php
│       ├── CronogramaRepositoryInterface.php
│       ├── VistoriaRepositoryInterface.php
│       └── HistoricoRepositoryInterface.php
├── Infrastructure/
│   └── Persistence/                  ← Implementacoes Eloquent das interfaces
│       └── Eloquent<Entidade>Repository.php
├── Application/                      ← (Opcional) UseCases para fluxos multi-step
│   └── UseCases/
│       ├── AtivarCronogramaUseCase.php
│       └── ValidarViagemUseCase.php
├── Services/                         ← Service Layer (SRP) — uma responsabilidade por classe
│   ├── PrestadorService.php
│   ├── CaminhaoService.php
│   ├── CronogramaService.php
│   ├── ViagemService.php
│   ├── VistoriaService.php
│   ├── HistoricoService.php
│   └── TdapExportBiService.php
├── DTOs/                             ← Imutaveis, transportam dados Request <-> Service
├── Http/
│   ├── Requests/                     ← Form Requests (validacao isolada)
│   └── Resources/                    ← JSON Resources (serializacao isolada)
├── Models/                           ← Eloquent (Anemic — sem regra de negocio dentro)
│   ├── Cronogramas/                  ← Subpasta por agregado quando >3 models relacionados
│   └── Vistorias/
├── Enums/                            ← PHP 8.1 backed enums
├── Mail/                             ← Mailables (Fase 5)
├── Observers/                        ← Hooks de evento Eloquent (Fase 5)
└── TdapServiceProvider.php           ← Bindings Interface => Implementation + singletons
```

**Princípios SOLID aplicados ao Service Layer:**

- **S**: cada Service tem 1 responsabilidade (`CronogramaService` não valida viagem — quem faz isso é `ViagemService`).
- **O**: novos tipos de evento de histórico estendem `HistoricoService::registrar()` via parâmetro `tipo_evento` (enum), sem editar a classe.
- **L**: `EloquentCronogramaRepository` é substituível por mock/fake nos testes — Controller e Service dependem da Interface.
- **I**: interfaces de repository expõem só o que cada cliente precisa (ex: `CronogramaRepositoryInterface` não tem método `findByCnpj` que pertence ao `Prestador`).
- **D**: Controllers e Services dependem das **interfaces** em `Domain/Repositories/`, nunca dos Models direto. O binding fica no Provider.

**Frontend Atomic Design (referência canônica: plano `2026-03-12-rat-create-atomic-design.md`):**

```
resources/js/
├── Components/
│   ├── Atoms/                        ← Sem estado de negocio, reutilizavel global
│   │   ├── Input/, Button/, Badge/, Table/, Typography/
│   │   └── Tdap/                     ← Atomos especificos (ex: TdapStatusBadge)
│   ├── Molecules/                    ← Composicao de 2+ atomos
│   │   ├── Form/                     ← TextField, ToggleField, DateField
│   │   └── Tdap/                     ← PrestadorCard, CaminhaoRow
│   ├── Organisms/                    ← Secoes complexas com estado proprio
│   │   └── Tdap/
│   │       ├── PrestadorForm.vue
│   │       ├── CronoCaminhoesTable.vue
│   │       └── VistoriaChecklistGroup.vue
│   └── Sections/                     ← Blocos nomeados de uma Page (ex: CronogramaResumoSection)
├── Templates/                        ← Esqueleto de layout reutilizavel (ex: CrudIndexTemplate)
│   └── Tdap/
│       ├── TdapIndexTemplate.vue     ← Layout: titulo + filtros + tabela + paginacao
│       └── TdapFormTemplate.vue      ← Layout: header + body + footer com acoes
├── Pages/                            ← Componentes Inertia (1 por rota), compoem Templates
│   └── Tdap/
└── composables/
    └── tdap/                         ← Logica reusavel: useCronograma, useTdapDashboard
```

Regra de ouro do Atomic Design: **Pages não contêm HTML estrutural — só montam Templates passando Sections/Organisms como slots e fornecendo props vindos do Inertia.**

---

## Modelo de Negócio Global

Fluxo macro do TDAP (mantido na migração):

```
[Cadastros base]                [Instrumentos contratuais]
 Prestador  ─┐                   ┌─ Ata
 Caminhao ──┼──> usados por ──> ─┤
            │                    └─ Lote (subdivisão da Ata por município)
            │                                │
            │                                v
            │                          [Execução]
            │                          Cronograma (numero, periodo, consumo)
            │                            │
            │                            ├─ CronoCaminhao (alocacao de cada caminhao)
            │                            │      │
            │                            │      └─ CronoViagem (registro de viagem feita)
            │                            │
            └────────────────> Vistoria (inspecao de veiculo)
                                         │
                                         v
                                   Historico (auditoria geral)
```

**Atores** (perfis Spatie):

- `tdap.admin` — administra cadastros, atas e parâmetros do módulo.
- `tdap.gestor` — cria e altera cronogramas, valida viagens.
- `tdap.prestador` — visualiza próprios cronogramas, registra viagens. Acesso restrito (cnpj match).
- `tdap.vistoriador` — registra e aprova vistorias de veículos.
- `tdap.viewer` — leitura apenas (PowerBI, auditoria).

**Integrações que serão preservadas:**

- PMDA (Planos Municipais de Defesa Civil) — `pmda_ponto`, `ponto_captacao` (já existem no novo sistema).
- Municípios — `municipios` (já presente).
- E-mail SMTP — notificação de cronograma ativado (`emails.tdap.cronograma_ativado`).
- LogsModelChanges (trait de auditoria existente no legado, equivalente já presente no novo SDC).

---

## Mapa de Arquivos (Visão Geral por Fase)

| Fase | DB (Postgres 18 + PostGIS) | Backend (DDD + SOLID) | Frontend (Atomic Design) |
|------|----------------------------|-----------------------|--------------------------|
| 0 — Fundação | nenhuma migration | renomeia stub `Tdap`→`Almoxarifado`; cria `app/Modules/Tdap/{Controllers,Domain,Infrastructure,Services,DTOs,Http,Models,Enums}` + Provider + rotas + permissões | renomeia `Pages/Tdap/*` stub→`Pages/Almoxarifado/*`; cria pasta `Pages/Tdap/`, `Templates/Tdap/`, `Components/{Atoms,Molecules,Organisms}/Tdap/`; sidebar |
| 1 — Cadastros | `tdap_prestadores`, `tdap_caminhoes` (com `geography(Point)` em endereço opcional) | `PrestadorRepositoryInterface`+`Eloquent…Repository`, idem Caminhao; `PrestadorService`/`CaminhaoService`; `StorePrestadorRequest`/`UpdatePrestadorRequest`; Resources | Atoms `CnpjInput`, `PlacaInput`; Molecules `PrestadorCard`, `CaminhaoRow`; Organisms `PrestadorForm`, `CaminhaoForm`; Template `TdapCrudTemplate`; Pages Index/Create/Edit |
| 2 — Instrumentos contratuais | `tdap_atas`, `tdap_lotes` | `AtaRepositoryInterface`/`LoteRepositoryInterface`; `AtaService` (regra: ata com cronograma ativo não pode ser excluída) | Organisms `AtaForm`, `LoteFormInline`; Section `AtaLotesSection`; Pages Atas/* |
| 3 — Cronograma (CORE) | `tdap_cronogramas`, `tdap_crono_caminhoes`, `tdap_crono_viagens`; `jsonb` em `stored_*`; índice `GIN` em `stored_caminhoes` | `CronogramaRepositoryInterface` + `EloquentCronogramaRepository`; UseCases `AtivarCronogramaUseCase`, `ValidarViagemUseCase`; Services `CronogramaService`, `ViagemService`, `CronogramaExportService` | Organisms `CronogramaForm`, `CronoCaminhoesTable`, `ViagensRegistroModal`; Sections `CronogramaResumoSection`, `CronogramaCaminhoesSection`, `CronogramaViagensSection`; Template `TdapDetailTemplate`; composables `useCronograma`, `useViagens` |
| 4 — Vistoria | `tdap_vistorias` (27 + 7 booleans) | `VistoriaRepositoryInterface`+`Eloquent…Repository`; `VistoriaService` (regra de vigência 12 meses); acessor `vistoriaVigente` em Caminhao | Atoms `ChecklistItem`; Molecules `ChecklistItemWithObs`; Organisms `VistoriaChecklistGroup`, `VistoriaFichaForm`; Sections `VistoriaIdentificacaoSection`, `VistoriaEstruturalSection`, `VistoriaTanqueSection`, `VistoriaParecerSection` |
| 5 — Histórico, e-mail, polish | `tdap_historicos` (`payload jsonb`, índice composto) | `HistoricoRepositoryInterface`+`Eloquent…Repository`; `HistoricoService` (registrar); Observers `CronogramaObserver`/`ViagemObserver`/`VistoriaObserver`; Mailable `CronogramaAtivadoMail`; `TdapApiController` + `TdapExportBiService` | Organisms `KpiCard`, `EntregasPorMesChart`, `ViagensPendentesList`; Template `TdapDashboardTemplate`; Page `Dashboard.vue`; tab "Auditoria" reusável |
| 6 — Workflow Event-Driven | `tdap_processos` (agregado raiz), `outbox_events`, `processed_events`, `tdap_processo_projecoes` (read model) | `ProcessoTdap` aggregate + States declarativos (`spatie/laravel-model-states`); Domain Events imutáveis em `Domain/Events/`; Listeners cross-module (`Decretacoes`→TDAP, TDAP→PAE); `OutboxDispatcher` job; Sagas; `EventStore` para replay | Pages `Processos/{Index,Show}`; Organisms `SwimlaneBoard`, `ProcessoTimeline`, `EstadoBadge`; Sections `ProcessoEventosSection` (event log temporal); composables `useProcessoTdap`, `useSwimlanes` |

**Intocados (já existem no novo SDC):** `municipios`, `users`, `pmda_pontos`, `ponto_captacao`, `Spatie/Permissions`, `config/permissions.php` (apenas adiciona entradas TDAP), `LogsModelChanges` equivalente.

**Ordem de execução:** **sequencial**. Cada fase fecha um valor de negócio e deve ser mergeada antes da próxima começar. Tarefas dentro da mesma fase podem ser paralelizadas (DB primeiro, depois Backend e Frontend em paralelo).

---

## Convenções Arquiteturais Obrigatórias

Estas três convenções são **invariantes** durante todas as fases. Qualquer arquivo criado que as viole deve ser refatorado antes do merge.

### 1. Backend — DDD + Service Layer SOLID

Toda entidade de domínio segue o trio **Interface (Domain) → Implementação (Infrastructure) → Consumo via Service (Application)**:

```php
// 1) Contrato em Domain/Repositories — depende de zero infra
namespace App\Modules\Tdap\Domain\Repositories;

interface CronogramaRepositoryInterface
{
    public function findById(int $id): ?Cronograma;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Cronograma;
    public function update(int $id, array $data): Cronograma;
    public function delete(int $id): void;
    public function findCronogramasAtivos(int $prestadorId): Collection;
}

// 2) Implementacao em Infrastructure/Persistence — unica classe que toca Eloquent
namespace App\Modules\Tdap\Infrastructure\Persistence;

final class EloquentCronogramaRepository implements CronogramaRepositoryInterface
{
    public function findById(int $id): ?Cronograma
    {
        return Cronograma::with(['caminhoes.caminhao','prestador','lote'])->find($id);
    }
    // ... demais metodos
}

// 3) Service depende SO da Interface (DIP)
namespace App\Modules\Tdap\Services;

final class CronogramaService
{
    public function __construct(
        private readonly CronogramaRepositoryInterface $repository,
        private readonly HistoricoService $historico,
        private readonly Mailer $mailer,
    ) {}

    public function ativar(int $id, int $userId): Cronograma { /* ... */ }
}

// 4) Binding no Provider
public function register(): void
{
    $this->app->bind(CronogramaRepositoryInterface::class, EloquentCronogramaRepository::class);
    $this->app->singleton(CronogramaService::class);
}
```

**Proibido:**
- Controller injetando Model direto (`Cronograma $cronograma` por Route Model Binding é OK; instanciar dentro do método não).
- Service chamando `Cronograma::query()` direto — sempre via Repository.
- Regra de negócio dentro de Model. Models são **anêmicos** (só relations + casts + scopes triviais).

### 2. Frontend — Atomic Design Estrito

Composição **bottom-up** com 6 níveis:

```
Atoms        — Sem state de negocio. Reutilizavel global. (ex: CnpjInput, ToggleInput)
Molecules    — 2+ atomos. Sem fetch. (ex: TextField = label + input + error)
Sections     — Bloco nomeado dentro de uma Page. State local. (ex: CronogramaResumoSection)
Organisms    — Componentes complexos com state proprio + emit/api. (ex: CronoCaminhoesTable)
Templates    — Esqueleto de layout reutilizavel sem dados. (ex: TdapCrudTemplate)
Pages        — Componente Inertia. Recebe props, monta Template. SEM HTML inline.
```

**Proibido em Pages:**
- `<table>`, `<form>`, `<div class="grid">` ou qualquer HTML estrutural inline.
- Handlers de submit/validação direto na page. Empurrar para o Organism via emit.
- Chamada `axios`/`fetch` direta. Usar composables (`useCronograma.js`) com TanStack Vue Query.

**Page mínima válida:**

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapDetailTemplate from '@/Templates/Tdap/TdapDetailTemplate.vue';
import CronogramaResumoSection from '@/Components/Sections/Tdap/CronogramaResumoSection.vue';
import CronogramaCaminhoesSection from '@/Components/Sections/Tdap/CronogramaCaminhoesSection.vue';
import CronogramaViagensSection from '@/Components/Sections/Tdap/CronogramaViagensSection.vue';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ cronograma: Object, podeAtivar: Boolean });
</script>

<template>
  <Head :title="`Cronograma #${cronograma.numero}`" />
  <TdapDetailTemplate :title="`Cronograma #${cronograma.numero}`">
    <template #resumo>   <CronogramaResumoSection    :cronograma="cronograma" /></template>
    <template #caminhoes><CronogramaCaminhoesSection :cronograma="cronograma" /></template>
    <template #viagens>  <CronogramaViagensSection   :cronograma="cronograma" /></template>
  </TdapDetailTemplate>
</template>
```

### 3. Banco — Postgres 18 + PostGIS 3.6

Tipos preferidos:

| Caso de uso              | Coluna Postgres                   | Por quê não MySQL/legado         |
|--------------------------|-----------------------------------|----------------------------------|
| Snapshot mutável         | `jsonb`                           | Indexável (GIN), query path-aware |
| Localização (PMDA ponto) | `geography(Point, 4326)`          | Distância em metros nativa        |
| Status com poucos valores| `varchar` + `CHECK (status IN …)` | ENUMs Postgres engessam migrations|
| Soft delete              | `timestamp with time zone`        | TZ-aware                          |
| FK cascade               | `ON DELETE CASCADE`/`SET NULL`    | Idêntico ao legado, sintaxe Eloquent |

Configurações no Eloquent:

```php
$table->jsonb('stored_caminhoes')->nullable();
$table->geography('ponto_captacao', subtype: 'POINT', srid: 4326)->nullable();

DB::statement("CREATE INDEX idx_cronograma_stored ON tdap_cronogramas USING GIN (stored_caminhoes)");
DB::statement("CREATE INDEX idx_cronograma_geo ON tdap_cronogramas USING GIST (ponto_captacao)");
```

ENUMs do legado (`parecer ENUM('Aprovado','Reprovado')`) viram:

```php
$table->string('parecer', 20);
DB::statement("ALTER TABLE tdap_vistorias ADD CONSTRAINT chk_parecer CHECK (parecer IN ('Aprovado','Reprovado'))");
```

---

## Fase 0 — Fundação do Módulo

### Modelo de negócio

Realocar o esqueleto "tdap-estoque" presente no NewSDC para um nome neutro (`Almoxarifado`) e plantar a fundação do TDAP-água: ServiceProvider, arquivo de rotas, permissões Spatie, item de menu lateral, e o package skeleton vazio que as próximas fases vão preencher. Sem este passo, há conflito de namespace e o módulo de água nasce contaminado.

### DB

- [ ] **Step 0.1: Inventariar migrations stub do TDAP-estoque no NewSDC**

```bash
ls C:\Users\x24679188\Documents\Github\NewSDC\SDC\database\migrations\ | findstr tdap
```

Saída esperada: vazio (não há migrations criadas para o stub atual — apenas Controllers/Models/Services). Se houver, renomear o prefixo `tdap_` para `almox_` antes de qualquer execução de `php artisan migrate`.

### Backend

**Files:**
- Renomear pasta: `app/Modules/Tdap/` → `app/Modules/Almoxarifado/`
- Atualizar: `config/app.php` (provider)
- Atualizar: `config/permissions.php` (renomear chaves `tdap.products.*` → `almoxarifado.products.*`)
- Criar estrutura DDD nova: `app/Modules/Tdap/{Controllers,Domain/Repositories,Infrastructure/Persistence,Application/UseCases,Services,DTOs,Http/Requests,Http/Resources,Models,Enums,Mail,Observers}/.gitkeep`
- Criar: `app/Modules/Tdap/TdapServiceProvider.php`
- Criar: `routes/modules/tdap.php`

- [ ] **Step 0.2: Renomear o stub `Tdap` para `Almoxarifado`**

Renomear arquivos:

```
app/Modules/Tdap/Controllers/TdapDashboardController.php   → AlmoxarifadoDashboardController.php
app/Modules/Tdap/Controllers/TdapProductsController.php    → AlmoxarifadoProductsController.php
app/Modules/Tdap/Controllers/TdapRecebimentosController.php→ AlmoxarifadoRecebimentosController.php
app/Modules/Tdap/Controllers/TdapMovimentacoesController.php→ AlmoxarifadoMovimentacoesController.php
app/Modules/Tdap/Services/TdapService.php                  → AlmoxarifadoService.php
app/Modules/Tdap/TdapServiceProvider.php                   → AlmoxarifadoServiceProvider.php
app/Modules/Tdap/Enums/*                                   → ajustar namespaces
app/Modules/Tdap/Models/*                                  → manter classes (Product/Recebimento/Movimentacao), só ajustar namespace
```

`replace_all` em arquivos PHP: `App\Modules\Tdap` → `App\Modules\Almoxarifado`.

Em `config/app.php`:

ANTES:
```php
App\Modules\Tdap\TdapServiceProvider::class,
```
DEPOIS:
```php
App\Modules\Almoxarifado\AlmoxarifadoServiceProvider::class,
```

- [ ] **Step 0.3: Realocar chaves de permissão**

Em `config/permissions.php` renomear bloco `'TDAP'` → `'ALMOXARIFADO'` e prefixos `tdap.` → `almoxarifado.`.

- [ ] **Step 0.4: Criar provider novo do TDAP-água com bindings DDD**

Criar `app/Modules/Tdap/TdapServiceProvider.php` (espelha estrutura de `RatServiceProvider`):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Tdap;

use Illuminate\Support\ServiceProvider;

class TdapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings Interface => Eloquent (preenchidos fase a fase).
        // Exemplo (a partir da Fase 1):
        // $this->app->bind(
        //     \App\Modules\Tdap\Domain\Repositories\PrestadorRepositoryInterface::class,
        //     \App\Modules\Tdap\Infrastructure\Persistence\EloquentPrestadorRepository::class,
        // );

        // Services como singletons (stateless por design):
        // $this->app->singleton(\App\Modules\Tdap\Services\CronogramaService::class);
    }

    public function boot(): void
    {
        // Observers registrados na Fase 5:
        // \App\Modules\Tdap\Models\Cronograma::observe(\App\Modules\Tdap\Observers\CronogramaObserver::class);
    }
}
```

Registrar em `config/app.php`:
```php
App\Modules\Tdap\TdapServiceProvider::class,
```

- [ ] **Step 0.4b: Criar `.gitkeep` nos diretórios DDD vazios**

Para que a estrutura DDD apareça no git desde a Fase 0, ainda sem código:

```
app/Modules/Tdap/Domain/Repositories/.gitkeep
app/Modules/Tdap/Infrastructure/Persistence/.gitkeep
app/Modules/Tdap/Application/UseCases/.gitkeep
app/Modules/Tdap/Services/.gitkeep
app/Modules/Tdap/DTOs/.gitkeep
app/Modules/Tdap/Http/Requests/.gitkeep
app/Modules/Tdap/Http/Resources/.gitkeep
app/Modules/Tdap/Models/.gitkeep
app/Modules/Tdap/Enums/.gitkeep
app/Modules/Tdap/Mail/.gitkeep
app/Modules/Tdap/Observers/.gitkeep
```

- [ ] **Step 0.5: Criar arquivo de rotas do módulo**

Criar `routes/modules/tdap.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('tdap')->name('tdap.')->group(function () {
    // Endpoints serao adicionados em cada fase
});
```

Em `routes/web.php`, dentro do middleware `auth`:
```php
require __DIR__ . '/modules/tdap.php';
```

- [ ] **Step 0.6: Adicionar bloco TDAP em `config/permissions.php`**

```php
'TDAP' => [
    'Prestadores'  => ['view','create','edit','delete'],
    'Caminhoes'    => ['view','create','edit','delete'],
    'Atas'         => ['view','create','edit','delete'],
    'Lotes'        => ['view','create','edit','delete'],
    'Cronogramas'  => ['view','create','edit','ativar','prorrogar','export'],
    'Viagens'      => ['view','create','validar'],
    'Vistorias'    => ['view','create','edit','aprovar'],
    'Historico'    => ['view'],
    'Admin'        => ['admin'],
],
```

Cada entrada gera chave `tdap.prestadores.view` etc., compatível com middleware `can:`.

### Frontend

**Files:**
- Renomear: `resources/js/Pages/Tdap/*` → `resources/js/Pages/Almoxarifado/*`
- Renomear: `resources/js/Components/{Atoms,Molecules,Organisms}/Tdap/*` → `…/Almoxarifado/*`
- Criar pastas Atomic Design vazias do TDAP-água:
  - `resources/js/Pages/Tdap/.gitkeep`
  - `resources/js/Templates/Tdap/.gitkeep`
  - `resources/js/Components/Atoms/Tdap/.gitkeep`
  - `resources/js/Components/Molecules/Tdap/.gitkeep`
  - `resources/js/Components/Sections/Tdap/.gitkeep`
  - `resources/js/Components/Organisms/Tdap/.gitkeep`
  - `resources/js/composables/tdap/.gitkeep`
- Atualizar: `resources/js/Layouts/AuthenticatedLayout.vue` (sidebar)

- [ ] **Step 0.7: Renomear pages stub do estoque**

Mover diretórios e atualizar imports (Pages e Components em todos os níveis Atomic). Os `Inertia::render('Tdap/...')` nos controllers renomeados passam a apontar `Almoxarifado/...`.

- [ ] **Step 0.8: Plantar Templates base do TDAP**

Criar dois Templates reutilizáveis (preenchidos pelas Pages das próximas fases):

```vue
<!-- resources/js/Templates/Tdap/TdapCrudTemplate.vue -->
<template>
  <div class="p-4 sm:p-6">
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ title }}</h1>
      <slot name="actions" />
    </header>
    <slot name="filters" />
    <slot />
    <footer class="mt-4"><slot name="pagination" /></footer>
  </div>
</template>
<script setup>defineProps({ title: { type: String, required: true } });</script>
```

```vue
<!-- resources/js/Templates/Tdap/TdapDetailTemplate.vue -->
<template>
  <div class="p-4 sm:p-6 space-y-6">
    <header><h1 class="text-xl font-semibold">{{ title }}</h1></header>
    <slot name="resumo" />
    <slot name="caminhoes" />
    <slot name="viagens" />
    <slot name="auditoria" />
  </div>
</template>
<script setup>defineProps({ title: { type: String, required: true } });</script>
```

- [ ] **Step 0.9: Inserir item TDAP no menu lateral**

Em `resources/js/Layouts/AuthenticatedLayout.vue` (ou `Sidebar.vue`), adicionar entrada `TDAP — Água Potável` apontando para `route('tdap.dashboard')` (vazia por ora; ganha conteúdo na Fase 5). Adicionar item `Almoxarifado` apontando para o stub renomeado.

### Verificação de fim de fase

- [ ] `php artisan route:list | grep tdap` lista apenas o prefixo `/tdap` registrado, sem rotas filhas.
- [ ] `npm run build` compila sem erros de import quebrado.
- [ ] Login na aplicação mostra dois itens no menu: `TDAP (Agua Potavel)` e `Almoxarifado`.

---

## Fase 1 — Cadastros Base (Prestador e Caminhão)

### Modelo de negócio

**Prestador** é a pessoa jurídica contratada para transportar água. Identificado por **CNPJ único**, possui razão social, representante legal, contatos (e-mail obrigatório — recebe notificações de cronograma ativado), endereço. Não pode ser excluído se possuir caminhões ou ata vinculados.

**Caminhão** é o veículo-tanque do prestador. Identificado por **placa única** (Mercosul ou antiga, 8 chars). Possui marca, modelo, ano, cor, **capacidade do tanque** (m³). Vinculado a **um único prestador** (`prestador_id`). Caminhão sem vistoria aprovada não pode ser alocado em cronograma.

Regras invariantes:
1. CNPJ válido (algoritmo de DV) — usar mesma validação aplicada em `app/Modules/Decretacoes/`.
2. Placa única no sistema.
3. Soft delete em ambos (preserva integridade com histórico).

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000001_create_tdap_prestadores_table.php`
- Criar: `database/migrations/2026_05_11_000002_create_tdap_caminhoes_table.php`

- [ ] **Step 1.1: Migration `tdap_prestadores` (Postgres 18)**

Adaptar da legado (`2025_02_27_134854_create_table_tdap_prestador.php`). Trocar `tdap_prestador` → `tdap_prestadores`, adicionar `softDeletes()` (timestamp TZ-aware no Postgres) e `unique` em `cnpj`. Campos: `nome`, `cnpj` (unique 18), `representante`, `email`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf` (2), `cep` (10). Adicional Postgres: índice em `(uf, cidade)` para filtros do dashboard.

```php
$table->id();
$table->string('nome', 110);
$table->string('cnpj', 18)->unique();
$table->string('representante', 100)->nullable();
$table->string('email', 110);
$table->string('tel1', 20)->nullable();
$table->string('tel2', 20)->nullable();
$table->string('endereco', 150)->nullable();
$table->string('bairro', 50)->nullable();
$table->string('cidade', 60)->nullable();
$table->string('uf', 2)->nullable();
$table->string('cep', 10)->nullable();
$table->timestamps();
$table->softDeletes();
$table->index(['uf', 'cidade']);
```

- [ ] **Step 1.2: Migration `tdap_caminhoes` (Postgres 18)**

Adaptar de `2025_03_19_141454_create_table_caminhao.php`. Campos: `placa` (unique 8), `cor`, `ano`, `marca`, `modelo`, `capacidade` (`decimal(8,2)` — corrige string do legado), `prestador_id` (FK `tdap_prestadores` ON DELETE RESTRICT), `softDeletes()`.

### Backend (DDD completo desde o primeiro CRUD)

**Files:**
- Models (anêmicos): `Prestador.php`, `Caminhao.php`
- Domain: `Domain/Repositories/PrestadorRepositoryInterface.php`, `CaminhaoRepositoryInterface.php`
- Infrastructure: `Infrastructure/Persistence/EloquentPrestadorRepository.php`, `EloquentCaminhaoRepository.php`
- Services: `Services/PrestadorService.php`, `Services/CaminhaoService.php`
- Controllers (finos): `Controllers/PrestadorController.php`, `Controllers/CaminhaoController.php`
- Http/Requests: `Store/Update {Prestador,Caminhao} Request`
- Http/Resources: `PrestadorResource`, `CaminhaoResource`, `PrestadorListResource`, `CaminhaoListResource`
- Provider: registrar bindings em `TdapServiceProvider::register()`
- Rotas: `routes/modules/tdap.php`

- [ ] **Step 1.3: Models anêmicos**

`Prestador` (`$table = 'tdap_prestadores'`, `SoftDeletes`) **apenas** com `$fillable`, `$casts`, `relations()`. Zero método estático de query, zero regra. Relações: `caminhoes()` `hasMany`, `cronogramas()` `hasMany`.

`Caminhao` idem. Relações: `prestador()` `belongsTo`, `vistorias()` `hasMany('placa_id')`, `vistoriaVigente()` `hasOne` (definido na Fase 4 via scope).

- [ ] **Step 1.4: Interfaces de Repository (Domain)**

```php
namespace App\Modules\Tdap\Domain\Repositories;

interface PrestadorRepositoryInterface
{
    public function findById(int $id): ?Prestador;
    public function findByCnpj(string $cnpj): ?Prestador;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Prestador;
    public function update(int $id, array $data): Prestador;
    public function delete(int $id): void;
}

interface CaminhaoRepositoryInterface
{
    public function findById(int $id): ?Caminhao;
    public function findByPlaca(string $placa): ?Caminhao;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function listarPorPrestador(int $prestadorId): Collection;
    public function create(array $data): Caminhao;
    public function update(int $id, array $data): Caminhao;
    public function delete(int $id): void;
}
```

- [ ] **Step 1.5: Implementações Eloquent (Infrastructure)**

`EloquentPrestadorRepository implements PrestadorRepositoryInterface` — única classe do módulo que faz `Prestador::query()`. Idem `EloquentCaminhaoRepository`.

- [ ] **Step 1.6: Services**

```php
final class PrestadorService
{
    public function __construct(
        private readonly PrestadorRepositoryInterface $repo,
    ) {}

    public function criar(array $data): Prestador { return $this->repo->create($data); }
    public function atualizar(int $id, array $data): Prestador { return $this->repo->update($id, $data); }
    public function excluir(int $id): void
    {
        $p = $this->repo->findById($id) ?? throw new ModelNotFoundException();
        if ($p->caminhoes()->exists() || $p->cronogramas()->exists()) {
            throw new \DomainException('Prestador com vinculos ativos nao pode ser excluido');
        }
        $this->repo->delete($id);
    }
}
```

- [ ] **Step 1.7: Form Requests**

Validar:
- `cnpj`: `required|cnpj|unique:tdap_prestadores,cnpj,{id}` (regra customizada `Rules\Cnpj`).
- `email`: `required|email`.
- Caminhão `placa`: `required|regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/|unique:...`.

- [ ] **Step 1.8: Controllers finos**

Controller só orquestra: valida via FormRequest → chama Service → renderiza via Inertia/Resource.

```php
final class PrestadorController extends Controller
{
    public function __construct(
        private readonly PrestadorRepositoryInterface $repo,
        private readonly PrestadorService $service,
    ) {}

    public function index(Request $r): Response
    {
        return Inertia::render('Tdap/Prestadores/Index', [
            'prestadores' => PrestadorListResource::collection(
                $this->repo->paginate(15, $r->only(['busca','uf']))
            ),
        ]);
    }

    public function store(StorePrestadorRequest $r): RedirectResponse
    {
        $this->service->criar($r->validated());
        return to_route('tdap.prestadores.index')->with('success', 'Prestador criado');
    }
    // edit, update, destroy seguem o mesmo padrao
}
```

- [ ] **Step 1.9: Bindings no Provider**

Adicionar em `TdapServiceProvider::register()`:

```php
$this->app->bind(PrestadorRepositoryInterface::class, EloquentPrestadorRepository::class);
$this->app->bind(CaminhaoRepositoryInterface::class, EloquentCaminhaoRepository::class);
$this->app->singleton(PrestadorService::class);
$this->app->singleton(CaminhaoService::class);
```

- [ ] **Step 1.10: Rotas**

Em `routes/modules/tdap.php`:

```php
Route::resource('prestadores', PrestadorController::class)
    ->except('show')
    ->names('prestadores');

Route::resource('caminhoes', CaminhaoController::class)
    ->except('show')
    ->names('caminhoes');
```

### Frontend (Atomic Design pirâmide completa)

**Files (de baixo para cima):**
- Atoms: `Components/Atoms/Tdap/CnpjInput.vue`, `Components/Atoms/Tdap/PlacaInput.vue`
- Molecules: `Components/Molecules/Tdap/PrestadorCard.vue`, `Components/Molecules/Tdap/CaminhaoRow.vue`
- Organisms: `Components/Organisms/Tdap/PrestadorForm.vue`, `Components/Organisms/Tdap/CaminhaoForm.vue`, `Components/Organisms/Tdap/PrestadoresTable.vue`, `Components/Organisms/Tdap/CaminhoesTable.vue`
- Sections: `Components/Sections/Tdap/PrestadoresFilterSection.vue`
- Templates: já criados na Fase 0 (`TdapCrudTemplate`)
- Pages: `Pages/Tdap/Prestadores/{Index,Create,Edit}.vue`, `Pages/Tdap/Caminhoes/{Index,Create,Edit}.vue`
- Composables: `composables/tdap/usePrestadores.js`, `composables/tdap/useCaminhoes.js`, `composables/useMaskBR.js`

- [ ] **Step 1.11: Atoms (input com máscara BR)**

`CnpjInput.vue`: estende `TextInput` (atom global existente) com máscara `xx.xxx.xxx/xxxx-xx` via `useMaskBR`. `PlacaInput.vue`: máscara `AAA-9A99` (Mercosul) ou `AAA-9999` (antiga), upper-case automático.

- [ ] **Step 1.12: Molecules (linha de tabela e card)**

`PrestadorCard.vue` recebe `prestador` e emite `edit`/`delete`. `CaminhaoRow.vue` idem para uso em `CaminhoesTable`. Sem chamada de API.

- [ ] **Step 1.13: Organisms (forms e tables)**

`PrestadorForm.vue` recebe `prestador?` (opcional, para edit) + `submitUrl` + `method`. Emite `success`/`error`. Usa `useForm` do Inertia. Reusado em `Create.vue` e `Edit.vue`.

`PrestadoresTable.vue` recebe `prestadores: PaginatedResource`, renderiza com `PrestadorCard` em mobile e `<table>` em desktop. Emite `delete`.

- [ ] **Step 1.14: Composables (TanStack Vue Query)**

`composables/tdap/usePrestadores.js`:

```js
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import { router } from '@inertiajs/vue3';

export function usePrestadores(filters) {
  const qc = useQueryClient();
  return {
    list: useQuery({ queryKey: ['tdap.prestadores', filters], queryFn: ... }),
    remove: useMutation({
      mutationFn: (id) => router.delete(route('tdap.prestadores.destroy', id)),
      onSuccess: () => qc.invalidateQueries({ queryKey: ['tdap.prestadores'] }),
    }),
  };
}
```

- [ ] **Step 1.15: Pages (HTML-livre, só Templates + Sections + Organisms)**

```vue
<!-- Pages/Tdap/Prestadores/Index.vue -->
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapCrudTemplate from '@/Templates/Tdap/TdapCrudTemplate.vue';
import PrestadoresFilterSection from '@/Components/Sections/Tdap/PrestadoresFilterSection.vue';
import PrestadoresTable from '@/Components/Organisms/Tdap/PrestadoresTable.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });
defineProps({ prestadores: Object });
</script>

<template>
  <Head title="TDAP — Prestadores" />
  <TdapCrudTemplate title="Prestadores">
    <template #actions>
      <Link :href="route('tdap.prestadores.create')" class="btn-primary">Novo</Link>
    </template>
    <template #filters><PrestadoresFilterSection /></template>
    <PrestadoresTable :prestadores="prestadores" />
  </TdapCrudTemplate>
</template>
```

### Verificação de fim de fase

- [ ] CRUD completo de Prestador e Caminhão funcional via UI.
- [ ] `php artisan test --filter=Prestador` passa.
- [ ] Tentativa de criar 2 prestadores com mesmo CNPJ retorna 422.
- [ ] Soft delete preserva registro em `tdap_prestadores` (coluna `deleted_at` preenchida).

---

## Fase 2 — Instrumentos Contratuais (Ata e Lote)

### Modelo de negócio

**Ata de Registro de Preços** é o contrato-pai que autoriza fornecimento durante vigência (`dt_inicio`/`dt_final`). Possui número (max 10), histórico textual e **múltiplos lotes**.

**Lote** é a subdivisão da Ata por **município**: cada lote vincula `ata_id + municipio_id + prestador_id` e fixa **quantidade de água contratada (m³)** e **valor unitário**. O lote é o que define quem fornece para quem dentro de um período.

Regras invariantes:
1. `dt_final > dt_inicio` na Ata.
2. Lote único por combinação `ata_id + municipio_id` (não pode haver duplicidade).
3. Ata com cronograma ativo não pode ser excluída.

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000003_create_tdap_atas_table.php`
- Criar: `database/migrations/2026_05_11_000004_create_tdap_lotes_table.php`

- [ ] **Step 2.1: Migration `tdap_atas`**

Campos: `numero` (string 10), `dt_inicio`, `dt_final`, `historico` (text, não 255 — corrige limitação do legado), `timestamps`, `softDeletes`.

- [ ] **Step 2.2: Migration `tdap_lotes`**

Adaptar do legado mas corrigir a FK quebrada `municipio_id->references('id')->on('municipio_id')` (typo) para `references('id')->on('municipios')`. Adicionar unique composto `(ata_id, municipio_id)`. Campos: `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua` (decimal 12,2), `valor` (decimal 10,2), `softDeletes`.

### Backend (segue tripé Interface → Implementação → Service)

**Files:**
- Models (anêmicos): `Ata.php`, `Lote.php`
- Domain: `Domain/Repositories/AtaRepositoryInterface.php`, `LoteRepositoryInterface.php`
- Infrastructure: `Infrastructure/Persistence/EloquentAtaRepository.php`, `EloquentLoteRepository.php`
- Services: `Services/AtaService.php` (regra "ata com cronograma ativo não pode ser excluída"), `Services/LoteService.php`
- Controllers (finos): `Controllers/AtaController.php`, `Controllers/LoteController.php`
- Http/Requests: `Store/Update {Ata,Lote} Request`
- Http/Resources: `AtaResource.php`, `LoteResource.php`, `AtaWithLotesResource.php`
- Provider: adicionar 2 bindings + 2 singletons em `TdapServiceProvider::register()`

- [ ] **Step 2.3: Relações**

`Ata::lotes()` `hasMany`, `Ata::cronogramas()` `hasMany`. `Lote::ata()`, `Lote::municipio()`, `Lote::prestador()`, `Lote::cronogramas()`.

- [ ] **Step 2.4: Rotas**

```php
Route::resource('atas', AtaController::class)->names('atas');
Route::resource('atas.lotes', LoteController::class)
    ->shallow()
    ->names('lotes');
```

### Frontend

**Files:**
- `Pages/Tdap/Atas/{Index,Create,Edit,Show}.vue`
- `Components/Organisms/Tdap/AtaForm.vue`
- `Components/Organisms/Tdap/LoteFormInline.vue`

- [ ] **Step 2.5: Show da Ata como página de detalhe**

A página `Atas/Show.vue` lista os Lotes inline, com botões "Adicionar Lote" e edição modal. Cronogramas ativos da Ata aparecem em aba secundária.

### Verificação de fim de fase

- [ ] Criação de Ata + N Lotes funciona ponta-a-ponta.
- [ ] Tentativa de criar Lote duplicado `(ata, municipio)` retorna 422.
- [ ] Soft delete de Ata com cronograma ativo bloqueado com mensagem clara.

---

## Fase 3 — Cronograma (CORE do Módulo)

### Modelo de negócio

**Cronograma** é a ordem operacional de fornecimento: dada uma Ata + Lote (município + prestador), define **período de execução** (`dt_inicio`/`dt_final`), **consumo diário** (m³), **dias** úteis, **fator** multiplicador, **nota de empenho**, **ponto de captação** (vinculado a PMDA), justificativa e número sequencial.

Sub-entidades:
- **CronoCaminhao** — alocação de cada caminhão do prestador ao cronograma: define `agua_prevista`, `num_viagens`, vincula `comunidade_id` (atende qual comunidade), guarda `agua_entregue` (somatório das viagens) e `vr_total` (valor consolidado). Ordem visual de exibição via `ordem`.
- **CronoViagem** — registro individual de cada viagem feita por um CronoCaminhao: `data_registro`, `data_aprovacao`, observações, status `validado` (NULL=pendente, 1=aprovada, 0=rejeitada).

Estado:
- `ativo = 0`: rascunho, editável livremente.
- `ativo = 1`: ativado → dispara e-mail ao prestador (`emails.tdap.cronograma_ativado`), bloqueia edição de cabeçalho, libera registro de viagens.
- Prorrogação: campos `dt_inicio_prorrogacao` e `dt_final_prorrogacao` estendem vigência sem criar novo cronograma.

Regras invariantes:
1. `numero` único na escala anual.
2. Cronograma só ativa se tiver ao menos 1 `CronoCaminhao` cadastrado.
3. Cada caminhão alocado precisa ter **vistoria aprovada vigente** (≤ 12 meses).
4. `agua_entregue` é derivada: somatório de viagens validadas × `capacidade` do caminhão.
5. Auditoria via `LogsModelChanges` trait.

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000005_create_tdap_cronogramas_table.php`
- Criar: `database/migrations/2026_05_11_000006_create_tdap_crono_caminhoes_table.php`
- Criar: `database/migrations/2026_05_11_000007_create_tdap_crono_viagens_table.php`

- [ ] **Step 3.1: Migration `tdap_cronogramas` (Postgres 18 + PostGIS + jsonb)**

Consolidar `create_table_cronograma` + `update_tdap_cronograma_table` + `add_deleted_at_to_tdap_cronograma_table` do legado em **uma única migration**. Tipos Postgres-nativos:

```php
$table->id();
$table->string('numero', 6)->unique();
$table->string('empenho', 15);
$table->foreignId('ata_id')->constrained('tdap_atas');
$table->foreignId('lote_id')->constrained('tdap_lotes');
$table->unsignedBigInteger('municipio_id');
$table->foreignId('prestador_id')->constrained('tdap_prestadores');
$table->string('cnpj', 18);                        // snapshot historico
$table->decimal('consumo_diario', 12, 2);
$table->unsignedSmallInteger('dias');
$table->float('fator');
$table->timestamp('dt_inicio');
$table->timestamp('dt_final');
$table->timestamp('dt_inicio_prorrogacao')->nullable();
$table->timestamp('dt_final_prorrogacao')->nullable();
$table->text('justificativa')->nullable();
$table->string('nota_empenho', 30);
$table->foreignId('ponto_captacao_id')->nullable()->constrained('pmda_pontos');
$table->foreignId('user_id')->constrained('users');
$table->boolean('viagem')->default(false);
$table->boolean('ativo')->default(false);
$table->text('observacao')->nullable();

// Snapshots jsonb (substituem text JSON do legado — indexaveis)
$table->jsonb('stored_caminhoes')->nullable();
$table->jsonb('stored_pmda_ponto')->nullable();
$table->jsonb('stored_municipio')->nullable();
$table->jsonb('stored_prestador')->nullable();

// PostGIS — ponto de captacao denormalizado para queries espaciais
$table->geography('ponto_captacao_geo', subtype: 'POINT', srid: 4326)->nullable();

$table->timestamps();
$table->softDeletes();
$table->index(['ativo','dt_inicio']);
```

Pós migration (raw):

```php
DB::statement("CREATE INDEX idx_cron_stored_cam ON tdap_cronogramas USING GIN (stored_caminhoes)");
DB::statement("CREATE INDEX idx_cron_geo ON tdap_cronogramas USING GIST (ponto_captacao_geo)");
```

- [ ] **Step 3.2: Migration `tdap_crono_caminhoes`**

Campos: `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista` (decimal 12,2), `num_viagens` (smallint), `agua_entregue` (decimal 12,2 default 0), `vr_total` (decimal 12,2 default 0), `ordem` (tinyint), `softDeletes`. FKs cascateadas.

- [ ] **Step 3.3: Migration `tdap_crono_viagens`**

Adaptar do legado. Campos: `crono_caminhao_id` (FK cascade), `data_registro`, `data_aprovacao` nullable, `obs`, `obs_aprovacao`, `validado` (tinyInteger nullable). Índices em `crono_caminhao_id`, `data_registro`, `validado`.

### Backend (DDD com UseCases para fluxos multi-step)

**Files:**
- Models (anêmicos, subpasta por agregado): `Models/Cronogramas/Cronograma.php`, `Models/Cronogramas/CronoCaminhao.php`, `Models/Cronogramas/CronoViagem.php`
- Domain: `Domain/Repositories/CronogramaRepositoryInterface.php`, `CronoCaminhaoRepositoryInterface.php`, `ViagemRepositoryInterface.php`
- Infrastructure: `Infrastructure/Persistence/EloquentCronogramaRepository.php`, `EloquentCronoCaminhaoRepository.php`, `EloquentViagemRepository.php`
- **Application/UseCases** (fluxos multi-step, transacionais): `Application/UseCases/AtivarCronogramaUseCase.php`, `Application/UseCases/ValidarViagemUseCase.php`, `Application/UseCases/ProrrogarCronogramaUseCase.php`
- Services: `Services/CronogramaService.php` (CRUD), `Services/ViagemService.php` (CRUD), `Services/CronogramaExportService.php`
- Controllers (finos): `CronogramaController.php`, `CronoCaminhaoController.php`, `ViagemController.php`
- Http/Requests: `Store/Update CronogramaRequest`, `AtivarCronogramaRequest`, `StoreViagemRequest`, `ValidarViagemRequest`
- Http/Resources: `CronogramaResource`, `CronogramaListResource`, `CronoCaminhaoResource`, `ViagemResource`
- DTOs: `DTOs/CronogramaSnapshotDTO.php` (encapsula os 4 campos `stored_*` jsonb)
- Enums: `StatusCronograma` (rascunho/ativo/encerrado), `StatusViagem` (pendente/aprovada/rejeitada)
- Mailable: `CronogramaAtivadoMail` (materializado na Fase 5)

- [ ] **Step 3.4: `AtivarCronogramaUseCase` (Application Layer)**

UseCase orquestra **transação** atômica + invariantes + e-mail + histórico. Depende apenas de **interfaces** (D do SOLID):

```php
namespace App\Modules\Tdap\Application\UseCases;

final class AtivarCronogramaUseCase
{
    public function __construct(
        private readonly CronogramaRepositoryInterface $cronogramas,
        private readonly VistoriaRepositoryInterface   $vistorias,
        private readonly HistoricoService              $historico,
        private readonly Mailer                        $mailer,
        private readonly ConnectionInterface           $db,
    ) {}

    public function execute(int $cronogramaId, int $userId): Cronograma
    {
        return $this->db->transaction(function () use ($cronogramaId, $userId) {
            $cronograma = $this->cronogramas->findById($cronogramaId)
                ?? throw new ModelNotFoundException();

            $caminhoes = $cronograma->caminhoes;
            if ($caminhoes->isEmpty()) {
                throw new DomainException('Cronograma exige ao menos 1 caminhao alocado');
            }

            foreach ($caminhoes as $cc) {
                $vistoria = $this->vistorias->vigenteParaCaminhao($cc->caminhao_id);
                if (!$vistoria) {
                    throw new DomainException(
                        "Caminhao {$cc->caminhao->placa} sem vistoria aprovada vigente"
                    );
                }
            }

            $cronograma = $this->cronogramas->update($cronogramaId, ['ativo' => true]);
            $this->mailer->send(new CronogramaAtivadoMail($cronograma));
            $this->historico->registrar('cronograma.ativado', $cronograma, null, ['user_id' => $userId]);

            return $cronograma;
        });
    }
}
```

Controller delega numa linha: `$useCase->execute($cronograma->id, auth()->id())`.

- [ ] **Step 3.5: `ValidarViagemUseCase`**

Mesma estrutura. `execute(int $viagemId, bool $aprovada, string $obs, int $userId)`:
1. Atualiza `validado`, `data_aprovacao`, `obs_aprovacao`.
2. Se aprovada, **recalcula** `agua_entregue` (soma viagens validadas × capacidade) e `vr_total` do `CronoCaminhao` parent.
3. Grava histórico (`viagem.validada` ou `viagem.rejeitada`).

Tudo dentro de uma `transaction`.

- [ ] **Step 3.6: Rotas**

```php
Route::resource('cronogramas', CronogramaController::class)->names('cronogramas');
Route::post('cronogramas/{cronograma}/ativar', [CronogramaController::class, 'ativar'])
    ->name('cronogramas.ativar')->middleware('can:tdap.cronogramas.ativar');
Route::get('cronogramas/{cronograma}/export', [CronogramaController::class, 'export'])
    ->name('cronogramas.export')->middleware('can:tdap.cronogramas.export');

Route::scopeBindings()->group(function () {
    Route::resource('cronogramas.caminhoes', CronoCaminhaoController::class)
        ->shallow()->names('crono_caminhoes');
    Route::resource('crono_caminhoes.viagens', ViagemController::class)
        ->shallow()->names('viagens');
});

Route::post('viagens/{viagem}/validar', [ViagemController::class, 'validar'])
    ->name('viagens.validar')->middleware('can:tdap.viagens.validar');
```

### Frontend

**Files:**
- `Pages/Tdap/Cronogramas/{Index,Create,Edit,Show}.vue`
- `Pages/Tdap/Cronogramas/Imprimir.vue` (versão print-friendly equivalente ao `imprimir_clean.blade.php` legado)
- `Pages/Tdap/Cronogramas/Fornecedor.vue` (visão restrita do prestador)
- `Components/Organisms/Tdap/CronogramaForm.vue`
- `Components/Organisms/Tdap/CronoCaminhoesTable.vue`
- `Components/Organisms/Tdap/ViagensRegistroModal.vue`
- `Components/Organisms/Tdap/CronogramaActivationBanner.vue`
- `composables/tdap/useCronograma.js` (Vue Query: listar, ativar, exportar)

- [ ] **Step 3.7: Cronograma Show**

Página `Show.vue` contém abas:
1. **Resumo** — cabeçalho com período, prestador, município, total previsto vs entregue.
2. **Caminhões alocados** — `CronoCaminhoesTable.vue`, drag-handle para `ordem`, botão "Registrar viagem" abre modal.
3. **Viagens** — listagem cronológica com filtro por status, ação "Validar" para gestores.
4. **Auditoria** — placeholder, preenchido na Fase 5.

- [ ] **Step 3.8: Visão restrita do prestador**

Rota `tdap/portal/cronogramas` (acesso `tdap.prestador`) lista apenas cronogramas onde `prestador_id == auth user's prestador`. Reaproveita Show com botões de edição desabilitados, exceto "registrar viagem".

### Verificação de fim de fase

- [ ] Criar Cronograma com Lote → alocar 2 Caminhões → tentar ativar sem Vistoria → recebe erro 422.
- [ ] Após cadastrar Vistorias aprovadas, ativação dispara update `ativo=1` (e-mail é validado na Fase 5).
- [ ] Registrar 3 viagens, validar 2 → `agua_entregue` atualizado automaticamente.
- [ ] Export Excel/PDF do cronograma gera arquivo equivalente ao legado.

---

## Fase 4 — Vistoria de Veículos

### Modelo de negócio

**Vistoria** é a inspeção técnica obrigatória do caminhão-tanque, com **27 itens estruturais** (documento, para-choque, placas, espelho retrovisor, motor, faróis, freio, pneus, buzina, extintor etc.) e **7 itens específicos do tanque** (pintura externa/interna, vazamento, mangote, válvula de expulsão, tampa de vedação, qualidade da água potável). Cada item é booleano + observação textual opcional. Resultado final: `parecer = Aprovado|Reprovado` + `ficha` (número do laudo).

Vistoria expirada (>12 meses) inviabiliza alocação do caminhão em cronograma (regra checada em Fase 3).

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000008_create_tdap_vistorias_table.php`

- [ ] **Step 4.1: Migration `tdap_vistorias`**

Replica fielmente o legado `2025_07_01_125638_create_tdap_vistoria_table.php`: `nome`, `edital`, `lote`, `placa_id` (FK caminhões), `modelo`, `cor`, `data`, `ano`, `capacidade`. 27 colunas boolean + 27 colunas `*_obs` text. 7 colunas boolean tanque + 7 `*_obs` text. `parecer` enum, `ficha` string, `softDeletes`. FK em `placa_id`.

### Backend (segue tripé Interface → Implementação → Service)

**Files:**
- Model (anêmico): `Models/Vistorias/Vistoria.php`
- Domain: `Domain/Repositories/VistoriaRepositoryInterface.php` (método-chave: `vigenteParaCaminhao(int $caminhaoId): ?Vistoria`)
- Infrastructure: `Infrastructure/Persistence/EloquentVistoriaRepository.php` — encapsula a query "parecer=Aprovado AND data >= now()-12 months"
- Service: `Services/VistoriaService.php` (CRUD + delega leitura vigente ao Repository)
- Controllers: `VistoriaController.php`
- Http/Requests: `Store/Update VistoriaRequest` (validações por bloco: identificação, 27 estruturais, 7 tanque, parecer)
- Http/Resources: `VistoriaResource.php`
- Enum: `ParecerVistoria` (`Aprovado`, `Reprovado`) — backed enum string
- DTO: `DTOs/VistoriaChecklistDTO.php` — agrupa os 34 campos boolean+obs em estruturas tipadas
- Provider: 1 binding + 1 singleton

- [ ] **Step 4.2: Acessor `vistoriaVigente` em Caminhao**

```php
public function vistoriaVigente()
{
    return $this->hasOne(Vistoria::class, 'placa_id')
        ->where('parecer', ParecerVistoria::Aprovado->value)
        ->where('data', '>=', now()->subMonths(12))
        ->latestOfMany('data');
}
```

- [ ] **Step 4.3: Rotas**

```php
Route::resource('vistorias', VistoriaController::class)->names('vistorias');
```

### Frontend (Atomic Design)

**Files (pirâmide):**
- Atoms: `Components/Atoms/Tdap/ChecklistItem.vue` (1 checkbox + label)
- Molecules: `Components/Molecules/Tdap/ChecklistItemWithObs.vue` (`ChecklistItem` + `textarea` observação)
- Organisms: `Components/Organisms/Tdap/VistoriaChecklistGroup.vue` (recebe `items: [{key,label}]`, v-model objeto), `VistoriaFichaForm.vue`
- Sections: `Components/Sections/Tdap/VistoriaIdentificacaoSection.vue`, `VistoriaEstruturalSection.vue` (27 items), `VistoriaTanqueSection.vue` (7 items), `VistoriaParecerSection.vue`
- Template: reusa `TdapFormTemplate` (criado na Fase 0)
- Pages: `Pages/Tdap/Vistorias/{Index,Create,Edit,Show}.vue`
- Composable: `composables/tdap/useVistoriaForm.js` (encapsula validação dos 34 booleans + parecer)

- [ ] **Step 4.4: Page composta apenas por Sections**

A Page `Create.vue` da Vistoria não tem **nenhum** `<input>` inline — só monta as Sections:

```vue
<template>
  <Head title="Nova vistoria" />
  <TdapFormTemplate title="Nova vistoria" @submit="submit">
    <VistoriaIdentificacaoSection v-model="form.identificacao" />
    <VistoriaEstruturalSection    v-model="form.estrutural" />
    <VistoriaTanqueSection        v-model="form.tanque" />
    <VistoriaParecerSection       v-model="form.parecer" />
  </TdapFormTemplate>
</template>
```

Cada Section consome `VistoriaChecklistGroup` passando seu próprio array de items. Os arrays vivem em `composables/tdap/useVistoriaItems.js` (fonte única da verdade dos labels).

### Verificação de fim de fase

- [ ] Vistoria criada com `parecer=Aprovado` e `data=hoje` é detectada como vigente.
- [ ] Cronograma da Fase 3 com caminhão vistoriado consegue ativar.
- [ ] Após simular `data = now()->subMonths(13)`, ativação volta a ser bloqueada.

---

## Fase 5 — Histórico, Notificações, Dashboard e PowerBI

### Modelo de negócio

**Histórico** é log unificado de eventos sensíveis do TDAP (ativação de cronograma, validação de viagem, ata excluída, vistoria aprovada). É complemento ao `LogsModelChanges` que cobre diffs campo-a-campo: o histórico aqui guarda **eventos de negócio** com `data_registro`, `obs`, `tipo_evento`, `entity_type`, `entity_id`, `user_id`.

**Notificação por e-mail**: Mailable `CronogramaAtivadoMail` é disparado ao ativar (já planejado em Fase 3, materializado aqui). Destinatários: `prestador.email` + fixo `tdap@ca.mg.gov.br` (ou `bmjcsugprg@lnovic.com` em local/test, comportamento legado preservado).

**Dashboard TDAP** consolida: cronogramas ativos × encerrados, m³ entregues do mês, prestadores ativos, viagens pendentes de validação.

**API PowerBI** expõe leitura agregada via endpoint protegido por token (reaproveita middleware `decretacoes.api.auth` existente).

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000009_create_tdap_historicos_table.php`

- [ ] **Step 5.1: Migration `tdap_historicos` (consolidada)**

Corrigir bug do legado (`obs` declarado 2x, ausência de FK). Campos: `data_registro` (dateTime), `tipo_evento` (string), `entity_type` (string), `entity_id` (unsignedBigInteger), `user_id` (FK users nullable), `obs` (text), `payload` (json nullable para snapshot), `timestamps`. Índices: `(entity_type, entity_id)`, `tipo_evento`, `data_registro`.

### Backend (DDD + integração API externa estilo RAT)

**Files:**
- Model (anêmico): `Models/Historico.php`
- Domain: `Domain/Repositories/HistoricoRepositoryInterface.php`
- Infrastructure: `Infrastructure/Persistence/EloquentHistoricoRepository.php`
- Service: `Services/HistoricoService.php` (`registrar(string $tipo, Model $entity, ?string $obs, ?array $payload)`)
- Service: `Services/TdapExportBiService.php` (consolida cronogramas + viagens em flat list, com paginação cursor)
- Observers: `Observers/CronogramaObserver`, `Observers/ViagemObserver`, `Observers/VistoriaObserver` (chamam `HistoricoService`)
- Mail: `Mail/CronogramaAtivadoMail.php` + view `resources/views/emails/tdap/cronograma_ativado.blade.php`
- Controllers: `Controllers/HistoricoController.php` (somente index/show), `app/Http/Controllers/Api/V1/Tdap/TdapApiController.php` (PowerBI), `Controllers/TdapDashboardController.php`
- Provider: registrar observers no `boot()` + bindings + singletons

- [ ] **Step 5.2: Registrar Observers no Provider**

Em `TdapServiceProvider::boot()`:

```php
Cronograma::observe(CronogramaObserver::class);
CronoViagem::observe(ViagemObserver::class);
Vistoria::observe(VistoriaObserver::class);
```

- [ ] **Step 5.3: Mailable + view de e-mail**

`CronogramaAtivadoMail` constrói assunto `"Cronograma nº {numero} liberado"` e renderiza `emails.tdap.cronograma_ativado` com `$cronograma` injetado. Equivalente a `Mail::send(...)` legado, agora estruturado.

- [ ] **Step 5.4: API PowerBI**

Endpoint `GET /api/v1/tdap/cronogramas?format=powerbi` retorna lista flat (1 linha por viagem validada com colunas denormalizadas: ata, lote, prestador, municipio, caminhao, data, agua_entregue, vr_total). Auth via middleware `decretacoes.api.auth` (renomear depois para `api.token` se quiser desacoplar). Registrar `'tdap'` em `config/integrations.php` allowlist (já presente).

- [ ] **Step 5.5: Rotas finais**

```php
Route::get('/', [TdapDashboardController::class, 'index'])->name('dashboard');
Route::resource('historicos', HistoricoController::class)
    ->only(['index','show'])->names('historicos');
```

E em `routes/api.php`:

```php
Route::middleware(['api.token','throttle:60,1'])->prefix('v1/tdap')->group(function () {
    Route::get('cronogramas', [TdapApiController::class, 'cronogramas']);
});
```

### Frontend

**Files:**
- `Pages/Tdap/Dashboard.vue`
- `Pages/Tdap/Historicos/{Index,Show}.vue`
- `Components/Organisms/Tdap/Dashboard/{KpiCard,EntregasPorMesChart,ViagensPendentesList}.vue`
- `composables/tdap/useTdapDashboard.js`

- [ ] **Step 5.6: Dashboard com ApexCharts**

Reaproveita `vue3-apexcharts` já no `package.json`. Cards superiores (KPIs) + 2 gráficos (entregas por mês, distribuição por prestador) + lista de viagens pendentes (link direto p/ tela de validação).

- [ ] **Step 5.7: Auditoria nas páginas Show**

Inserir aba "Auditoria" em `Cronogramas/Show.vue`, `Vistorias/Show.vue`, `Atas/Show.vue` que consome `GET /tdap/historicos?entity_type=X&entity_id=Y`.

### Verificação de fim de fase

- [ ] Ativação de cronograma gera e-mail (`storage/logs/laravel.log` em local, ou caixa de teste).
- [ ] Validação de viagem grava registro em `tdap_historicos` com `tipo_evento = 'viagem.validada'`.
- [ ] `curl -H "X-API-TOKEN: ..." /api/v1/tdap/cronogramas?format=powerbi` retorna JSON flat.
- [ ] Dashboard TDAP carrega < 1.5s com dados reais.

---

## Fase 6 — Workflow Orientado a Eventos (Event-Driven Monolith)

### Modelo de negócio

O fluxograma físico do TDAP atravessa **5 swimlanes** (Fomentação/Município, CEDEC, Diretoria de Contratos/Jurídico, Órgão de Licitação, Governador) e **4 fases macro**: (A) Habilitação, (B) Decretagem, (C) Execução, (D) Liquidação. As Fases 0-5 deste plano cobriram apenas o **núcleo da Fase C — Execução** (cadastros + cronograma + vistoria + viagem). Esta fase amarra o todo: um **agregado raiz `ProcessoTdap`** com **máquina de estados explícita** que coordena as entidades já implementadas e dialoga com `Decretacoes` (Fase B) e `Pae` (Fase D) por meio de **Domain Events** em vez de chamadas diretas.

**Por que event-driven monolith e não microserviços:**
1. Consistência transacional é regra de negócio: "sem decreto não empenha", "sem parecer jurídico não contrata" — exige ACID.
2. Latência inter-ator importa (horas, não dias). Saga distribuída adicionaria modos de falha sem ganho.
3. O Laravel já é modular: `app/Modules/Tdap`, `app/Modules/Decretacoes`, `app/Modules/Pae` são **bounded contexts**. Comunicação via eventos dá baixo acoplamento sem rede no meio.
4. Outbox + Listeners idempotentes preservam a mesma garantia "exactly-once" das filas distribuídas, mas dentro do banco único.

**Estados canônicos do `ProcessoTdap`:**

```
                +---------------+
                |   RASCUNHO    |  (município abre demanda)
                +-------+-------+
                        | submeter
                        v
                +-------+---------+
                | EM_HABILITACAO  |  (CEDEC verifica adimplência)
                +---+----+--------+
              recusar |  | aprovar
                      v  v
              +-------+--+-----------+
              | DECRETO_PENDENTE     |  (aguarda decreto Governador)
              +----------+-----------+
                         | decreto.publicado
                         v
              +----------+-----------+
              | EM_LICITACAO         |  (Compras gera ata/lote)
              +----------+-----------+
                         | ata.assinada
                         v
              +----------+-----------+
              | EM_EXECUCAO          |  (Cronogramas + Vistorias + Viagens)
              +----------+-----------+
                         | execucao.encerrada
                         v
              +----------+-----------+
              | LIQUIDACAO_PENDENTE  |  (PAE/Financeiro valida prestação)
              +---+-------+----------+
            ajustar|       | aprovar
                   |       v
                   |  +----+----+
                   |  | PAGO    |
                   |  +----+----+
                   |       |
                   v       v
              +-------------+
              | ENCERRADO   |
              +-------------+
```

**Cada transição é uma classe `Transition`** com:
- `guard()`: pré-condições (ex: `EmExecucao→LiquidacaoPendente` só se TODAS as viagens estiverem validadas).
- `handle()`: efeito colateral (persistir estado + emitir Domain Event no outbox).
- `notify()`: dispara notificações ao ator da próxima swimlane.

**Catálogo de Domain Events (versionados, imutáveis):**

| Evento | Disparado por | Consumido por |
|---|---|---|
| `ProcessoTdapAbertoV1` | Município ao criar `ProcessoTdap` | `HistoricoService`, `NotificacaoCedec` |
| `AdimplenciaVerificadaV1` | CEDEC após análise | Transita estado |
| `DecretoVinculadoV1` (cross-module) | `Decretacoes` ao publicar decreto estadual TDAP | Listener TDAP transita `DECRETO_PENDENTE→EM_LICITACAO` |
| `AtaAssinadaV1` | `AtaService::assinar` (Fase 2) | Transita TDAP + cria automaticamente os Lotes |
| `CronogramaAtivadoV1` | `CronogramaService::ativar` (Fase 3) | Email prestador + projection + histórico |
| `ViagemValidadaV1` | `ViagemService::validar` (Fase 3) | Recalcula `agua_entregue`; quando última, emite `ExecucaoConcluidaV1` |
| `ExecucaoConcluidaV1` | Saga `EncerramentoSaga` | Transita `EM_EXECUCAO→LIQUIDACAO_PENDENTE`; abre `PaeForm` |
| `PrestacaoContasAprovadaV1` (cross-module) | `Pae` após validação | Transita TDAP `→PAGO→ENCERRADO` |

**Padrões aplicados:**
- **Transactional Outbox** — eventos persistem em `outbox_events` **dentro da mesma transação** que altera estado. Job `DispatchOutboxJob` (a cada 1s) lê eventos não publicados, dispara via `event()` do Laravel e marca como `dispatched_at`. Garante: nunca há mudança de estado sem evento, nunca há evento sem mudança de estado.
- **Idempotência por listener** — cada listener registra `(event_id, listener_class)` em `processed_events` antes de executar; se já registrado, pula. Resolve retries sem efeito duplicado.
- **Event Versioning** — sufixo `V1`, `V2` no nome da classe permite evolução sem quebrar consumidores antigos.
- **Sagas** — fluxos que dependem de N eventos (ex: `EncerramentoSaga` espera última viagem + última nota fiscal) ficam em `Application/Sagas/`, persistem estado parcial em `tdap_sagas`.
- **Read Models / Projections** — `tdap_processo_projecoes` é desnormalizada, atualizada por listeners, consumida pelo dashboard sem joins pesados.

### DB

**Files:**
- Criar: `database/migrations/2026_05_11_000010_create_outbox_events_table.php`
- Criar: `database/migrations/2026_05_11_000011_create_processed_events_table.php`
- Criar: `database/migrations/2026_05_11_000012_create_tdap_processos_table.php`
- Criar: `database/migrations/2026_05_11_000013_create_tdap_processo_projecoes_table.php`
- Criar: `database/migrations/2026_05_11_000014_create_tdap_sagas_table.php`
- Editar: `database/migrations/2026_05_11_000005_create_tdap_cronogramas_table.php` (adicionar `processo_tdap_id` FK)

- [ ] **Step 6.1: Migration `outbox_events`**

```php
Schema::create('outbox_events', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('event_name', 150)->index();
    $t->unsignedTinyInteger('event_version')->default(1);
    $t->string('aggregate_type', 100)->index();
    $t->uuid('aggregate_id')->index();
    $t->jsonb('payload');
    $t->jsonb('metadata')->nullable();
    $t->timestampTz('occurred_at');
    $t->timestampTz('dispatched_at')->nullable()->index();
    $t->unsignedSmallInteger('dispatch_attempts')->default(0);
    $t->text('last_error')->nullable();
    $t->timestamps();
});
```

Postgres-specific: usar `jsonb` (com índice `GIN` em `payload->>type` se necessário), `timestampTz` para fuso horário correto, `uuid` nativo (extensão `pgcrypto` já presente).

- [ ] **Step 6.2: Migration `processed_events` (idempotência)**

```php
Schema::create('processed_events', function (Blueprint $t) {
    $t->uuid('event_id');
    $t->string('listener_class', 200);
    $t->timestampTz('processed_at')->useCurrent();
    $t->primary(['event_id','listener_class']);
});
```

- [ ] **Step 6.3: Migration `tdap_processos` (agregado raiz)**

```php
Schema::create('tdap_processos', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('numero', 20)->unique();
    $t->string('estado', 30)->index();
    $t->string('swimlane_atual', 30)->index();
    $t->unsignedBigInteger('municipio_id');
    $t->unsignedBigInteger('decretacao_id')->nullable();
    $t->unsignedBigInteger('pae_form_id')->nullable();
    $t->jsonb('contexto')->nullable();
    $t->timestampTz('aberto_em');
    $t->timestampTz('encerrado_em')->nullable();
    $t->foreignId('aberto_por')->constrained('users');
    $t->softDeletes();
    $t->timestamps();
    $t->foreign('municipio_id')->references('id')->on('municipios');
});
```

Constraint `CHECK (estado IN ('RASCUNHO','EM_HABILITACAO','DECRETO_PENDENTE','EM_LICITACAO','EM_EXECUCAO','LIQUIDACAO_PENDENTE','PAGO','ENCERRADO'))` para garantir integridade no nível do banco.

- [ ] **Step 6.4: Migration `tdap_processo_projecoes` (read model)**

Tabela desnormalizada com colunas pré-calculadas: `processo_id`, `numero`, `estado`, `municipio_nome`, `prestador_nome`, `total_cronogramas`, `total_agua_prevista`, `total_agua_entregue`, `total_viagens_validadas`, `dias_no_estado_atual`, `ultimo_evento`, `atualizado_em`. Atualizada por listeners de projeção, **nunca** escrita por Service direto.

- [ ] **Step 6.5: Migration `tdap_sagas`**

```php
Schema::create('tdap_sagas', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('saga_class', 200)->index();
    $t->uuid('correlation_id')->index();
    $t->string('estado_saga', 30);
    $t->jsonb('estado_acumulado');
    $t->timestampTz('iniciada_em');
    $t->timestampTz('completada_em')->nullable();
});
```

- [ ] **Step 6.6: Adicionar FK `processo_tdap_id` em `tdap_cronogramas`**

Edita migration consolidada da Fase 3 para adicionar nullable FK. Cronograma sem `processo_tdap_id` é cronograma "legado", criado sem agregado (compatibilidade durante rollout).

### Backend

**Files:**
- Modelo agregado: `app/Modules/Tdap/Models/ProcessoTdap.php`
- Estados: `app/Modules/Tdap/Domain/States/ProcessoTdapState.php` + 8 estados filhos (Rascunho, EmHabilitacao, DecretoPendente, EmLicitacao, EmExecucao, LiquidacaoPendente, Pago, Encerrado)
- Transitions: `app/Modules/Tdap/Domain/States/Transitions/<De>To<Para>.php`
- Domain Events: `app/Modules/Tdap/Domain/Events/{ProcessoTdapAbertoV1,AdimplenciaVerificadaV1,DecretoVinculadoV1,...}.php` (todos PHP 8 `readonly` com método estático `fromArray()`)
- Base abstrata: `app/Core/Events/DomainEvent.php` (se ainda não existir em `app/Core/`)
- Outbox: `app/Core/Outbox/{OutboxEvent.php, OutboxDispatcher.php, DispatchOutboxJob.php, RecordsDomainEvents.php (trait)}`
- Idempotency: `app/Core/Events/IdempotentListener.php` (abstract)
- Repository: `app/Modules/Tdap/Domain/Repositories/ProcessoTdapRepositoryInterface.php` + `Infrastructure/Persistence/EloquentProcessoTdapRepository.php`
- Service: `app/Modules/Tdap/Services/ProcessoTdapService.php` (abrir, transitar, encerrar)
- UseCases: `app/Modules/Tdap/Application/UseCases/{AbrirProcessoTdapUseCase,SubmeterHabilitacaoUseCase,RegistrarPagamentoUseCase}.php`
- Sagas: `app/Modules/Tdap/Application/Sagas/EncerramentoSaga.php`
- Listeners locais (TDAP): `app/Modules/Tdap/Listeners/{AtualizarProjecaoListener,RegistrarHistoricoListener,NotificarSwimlaneListener}.php`
- Listeners cross-module: `app/Modules/Tdap/Listeners/CrossModule/{OnDecretoPublicadoListener,OnPaeAprovadoListener}.php`
- Provider: atualizar `TdapServiceProvider::boot()` com binds + `Event::listen()`
- Console: `app/Console/Commands/{OutboxDispatch,EventReplay}.php`

- [ ] **Step 6.7: Base abstrata Domain Event**

```php
namespace App\Core\Events;

abstract readonly class DomainEvent
{
    public function __construct(
        public string $eventId,
        public string $aggregateType,
        public string $aggregateId,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    abstract public function eventName(): string;
    abstract public function eventVersion(): int;
    abstract public function payload(): array;

    public static function newId(): string { return (string) \Illuminate\Support\Str::uuid(); }
}
```

- [ ] **Step 6.8: Trait `RecordsDomainEvents` para agregados**

```php
namespace App\Core\Outbox;

trait RecordsDomainEvents
{
    /** @var DomainEvent[] */
    private array $pendingEvents = [];

    protected function recordEvent(\App\Core\Events\DomainEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }

    public function pullPendingEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];
        return $events;
    }
}
```

`ProcessoTdap` usa o trait; ao salvar, o `EloquentProcessoTdapRepository::save()` chama `pullPendingEvents()` e persiste no `outbox_events` **dentro da mesma transação** do `update()`. Sem `event()` direto — vai tudo pelo outbox.

- [ ] **Step 6.9: Estados via spatie/laravel-model-states**

```bash
docker compose exec app composer require spatie/laravel-model-states
```

```php
// ProcessoTdapState.php
abstract class ProcessoTdapState extends \Spatie\ModelStates\State
{
    abstract public function label(): string;
    abstract public function swimlane(): string;

    public static function config(): \Spatie\ModelStates\StateConfig
    {
        return parent::config()
            ->default(Rascunho::class)
            ->allowTransition(Rascunho::class, EmHabilitacao::class, RascunhoToEmHabilitacao::class)
            ->allowTransition(EmHabilitacao::class, DecretoPendente::class)
            ->allowTransition(DecretoPendente::class, EmLicitacao::class, DecretoPendenteToEmLicitacao::class)
            ->allowTransition(EmLicitacao::class, EmExecucao::class)
            ->allowTransition(EmExecucao::class, LiquidacaoPendente::class, EmExecucaoToLiquidacao::class)
            ->allowTransition(LiquidacaoPendente::class, Pago::class)
            ->allowTransition(Pago::class, Encerrado::class);
    }
}
```

Cada `Transition` é classe com `canTransition(): bool` (guard) e `handle(): State` (efeito). Ex: `EmExecucaoToLiquidacao::canTransition()` valida que todas viagens dos cronogramas vinculados estão validadas.

- [ ] **Step 6.10: OutboxDispatcher + Job**

```php
// app/Core/Outbox/OutboxDispatcher.php
public function dispatchPending(int $batch = 100): int
{
    $events = OutboxEvent::query()
        ->whereNull('dispatched_at')
        ->orderBy('occurred_at')
        ->limit($batch)
        ->lockForUpdate() // SELECT ... FOR UPDATE SKIP LOCKED no Postgres
        ->get();

    foreach ($events as $row) {
        try {
            DB::transaction(function () use ($row) {
                $eventClass = $this->resolveClass($row->event_name, $row->event_version);
                $event = $eventClass::fromOutbox($row);
                event($event);
                $row->update(['dispatched_at' => now()]);
            });
        } catch (\Throwable $e) {
            $row->update([
                'dispatch_attempts' => $row->dispatch_attempts + 1,
                'last_error' => $e->getMessage(),
            ]);
        }
    }
    return $events->count();
}
```

Postgres-specific: `lockForUpdate()->skipLocked()` permite múltiplos workers em paralelo sem contenção (Horizon com N processos).

```php
// app/Console/Commands/OutboxDispatch.php
public function handle(OutboxDispatcher $disp): int
{
    while (! $this->shouldQuit()) {
        $n = $disp->dispatchPending(100);
        if ($n === 0) sleep(1);
    }
    return self::SUCCESS;
}
```

Roda como processo Horizon supervisado: `php artisan outbox:dispatch`.

- [ ] **Step 6.11: IdempotentListener (abstract)**

```php
abstract class IdempotentListener implements ShouldQueue
{
    abstract protected function execute(DomainEvent $event): void;

    public function handle(DomainEvent $event): void
    {
        $key = ['event_id' => $event->eventId, 'listener_class' => static::class];

        DB::transaction(function () use ($event, $key) {
            $inserted = DB::table('processed_events')->insertOrIgnore($key + ['processed_at' => now()]);
            if (! $inserted) return; // já processado, pula
            $this->execute($event);
        });
    }
}
```

Listeners cross-module **devem** estender essa classe. Garante exactly-once mesmo com retry da fila.

- [ ] **Step 6.12: Cross-module — escutar Decretacoes**

Em `app/Modules/Decretacoes/Services/EntradaProcessoService::publicar()`, ao publicar um decreto cujo `TipoDecreto === DecretoTdap`, gravar evento no outbox (Decretacoes também usa o mesmo `app/Core/Outbox`):

```php
$this->outbox->record(new DecretoPublicadoV1(
    eventId: DomainEvent::newId(),
    aggregateType: 'Decretacao',
    aggregateId: (string) $decreto->id,
    occurredAt: new \DateTimeImmutable(),
    metadata: ['tipo' => $decreto->tipo->value, 'processo_tdap_id' => $decreto->processo_tdap_id],
));
```

No TDAP, `OnDecretoPublicadoListener extends IdempotentListener` recebe o evento e chama `ProcessoTdapService::vincularDecreto()` que transita `DECRETO_PENDENTE → EM_LICITACAO`. **Decretações nunca importa código do TDAP** — só publica o evento.

- [ ] **Step 6.13: Saga `EncerramentoSaga`**

Espera 2 eventos `(ViagemValidadaV1 da última viagem prevista do cronograma)` + `(NotaFiscalEmitidaV1 do prestador)`. Quando ambos chegam, emite `ExecucaoConcluidaV1` que transita `EM_EXECUCAO → LIQUIDACAO_PENDENTE`. Estado parcial persiste em `tdap_sagas`.

- [ ] **Step 6.14: Atualizar `CronogramaService::ativar`**

Em vez de `Mail::send` direto (planejado nas Fases 3/5), gravar `CronogramaAtivadoV1` no outbox. Listener `EnviarEmailCronogramaListener` consome e dispara o Mailable. Mailable vira **consumer**, não orquestrador.

- [ ] **Step 6.15: Comando `event:replay`**

```bash
php artisan event:replay --aggregate-id=<uuid> --from=2026-05-01 --to=2026-05-11 --listener=AtualizarProjecaoListener
```

Re-dispara eventos do outbox para um listener específico. Permite reconstruir `tdap_processo_projecoes` do zero sem precisar tocar o estado do agregado. Útil quando a projeção quebra ou ganha colunas novas.

- [ ] **Step 6.16: Rotas Workflow**

Em `routes/modules/tdap.php`:

```php
Route::prefix('processos')->name('processos.')->group(function () {
    Route::get('/', [ProcessoTdapController::class, 'index'])->name('index');
    Route::post('/', [ProcessoTdapController::class, 'store'])->name('store')
        ->middleware('can:tdap.processos.create');
    Route::get('/{processo}', [ProcessoTdapController::class, 'show'])->name('show');
    Route::post('/{processo}/transitar/{estadoAlvo}', [ProcessoTdapController::class, 'transitar'])
        ->name('transitar')->middleware('can:tdap.processos.transitar');
    Route::get('/{processo}/eventos', [ProcessoTdapController::class, 'eventos'])->name('eventos');
});

Route::get('/swimlanes', [SwimlaneBoardController::class, 'index'])->name('swimlanes');
```

- [ ] **Step 6.17: Horizon supervisor**

Adicionar em `config/horizon.php`:

```php
'outbox' => [
    'connection' => 'redis',
    'queue' => ['outbox'],
    'balance' => 'simple',
    'processes' => 2,
    'tries' => 5,
    'timeout' => 60,
],
```

Comando `outbox:dispatch` roda em background como processo supervisado (não fila), `IdempotentListener` roda em fila `outbox`.

### Frontend

**Files:**
- Pages: `resources/js/Pages/Tdap/Processos/{Index,Show,Create}.vue`, `resources/js/Pages/Tdap/Swimlanes.vue`
- Templates: `resources/js/Templates/Tdap/ProcessoDetalheTemplate.vue` (header + state badge + tabs)
- Organisms: `resources/js/Components/Organisms/Tdap/Processo/{SwimlaneBoard,ProcessoTimeline,EstadoTransicoesPanel,EventoLogTable}.vue`
- Molecules: `resources/js/Components/Molecules/Tdap/{EstadoBadge,SwimlaneTag,TransicaoButton,EventoCard}.vue`
- Atoms: `resources/js/Components/Atoms/Tdap/{EstadoIcon,SwimlaneColor}.vue`
- Sections: `resources/js/Components/Sections/Tdap/{ProcessoCabecalhoSection,ProcessoEventosSection,ProcessoTransicoesSection,ProcessoRelacionadosSection}.vue`
- Composables: `resources/js/composables/tdap/{useProcessoTdap,useSwimlanes,useEventoStream}.js`

- [ ] **Step 6.18: `SwimlaneBoard.vue` (Kanban por swimlane)**

Lê de `tdap_processo_projecoes`. Colunas = swimlanes (`fomentacao`, `cedec`, `juridico`, `licitacao`, `governador`). Cards = processos. Click no card abre `Show`. Drag-drop **desabilitado** — transições só por botão de ação que respeita guards.

- [ ] **Step 6.19: `ProcessoTimeline.vue` (eventos em ordem cronológica)**

Renderiza lista vertical: cada item é um Domain Event do outbox (já dispatched). Mostra `eventName`, `occurredAt`, `payload` resumido, ator (`metadata.user_id`). Suporta filtro por `eventName`. Como funciona como **fonte da verdade auditável**, substitui parte do que a Fase 5 colocou em `tdap_historicos` (que vira read-model construído por listener).

- [ ] **Step 6.20: `EstadoBadge.vue` + `TransicaoButton.vue`**

Badge muda cor por estado. Botões "Submeter para Habilitação", "Aprovar Adimplência", etc. só aparecem se o estado atual permite a transição **e** o user tem permissão. Lógica de habilitação em `useProcessoTdap.transicoesDisponiveis(processo)` (vem do backend, não calculado no front).

- [ ] **Step 6.21: `useEventoStream.js`**

Composable que faz polling a cada 5s em `/tdap/processos/{id}/eventos?since=<ultimo_id>` para detectar novos eventos no outbox. Atualiza timeline em tempo "quase real" sem precisar de WebSocket. Quando Echo/Reverb estiver disponível no projeto, troca por subscrição em canal privado.

### Verificação de fim de fase

- [ ] Criar `ProcessoTdap` via UI → estado `RASCUNHO`, evento `ProcessoTdapAbertoV1` em `outbox_events` com `dispatched_at` preenchido após ≤2s.
- [ ] Forçar transição inválida (ex: `RASCUNHO → PAGO`) retorna 422 com mensagem da `Transition::guard()`.
- [ ] Publicar decreto em `Decretacoes` com `processo_tdap_id` preenchido → 5s depois, `ProcessoTdap` está em `EM_LICITACAO` e timeline mostra `DecretoVinculadoV1`.
- [ ] Matar o worker `outbox:dispatch` no meio de um batch, reiniciar → todos os eventos pendentes acabam dispatched, **nenhum listener foi chamado duas vezes** (verificar `processed_events`).
- [ ] `event:replay --listener=AtualizarProjecaoListener` reconstrói `tdap_processo_projecoes` sem alterar `outbox_events` nem estado dos agregados.
- [ ] `SwimlaneBoard` carrega < 1s com 500 processos (consulta usa `tdap_processo_projecoes`, não joins).
- [ ] Saga `EncerramentoSaga` testada: aprovar última viagem + emitir NF em qualquer ordem → estado avança para `LIQUIDACAO_PENDENTE`.

### Notas de operação

- **Janela de visibilidade do outbox**: monitorar `outbox_events WHERE dispatched_at IS NULL AND occurred_at < NOW() - INTERVAL '30 seconds'` em Grafana. Alerta se > 0 por mais de 1 min.
- **Limpeza**: cron noturno move `outbox_events` com `dispatched_at < NOW() - INTERVAL '90 days'` para tabela arquivo `outbox_events_archive` (mesma estrutura, particionada por mês).
- **Schemas Postgres**: eventos podem usar **logical replication slot** futuramente se for necessário publicar para fora do monolito. Não fazer agora.

---

## Checklist Final de Validação

**Smoke geral:**
- [ ] `docker compose exec app php artisan migrate:fresh --seed` roda sem erro com todas as **14 migrations TDAP** (9 das Fases 0-5 + 5 da Fase 6: `outbox_events`, `processed_events`, `tdap_processos`, `tdap_processo_projecoes`, `tdap_sagas`).
- [ ] `docker compose exec app php artisan route:list | grep tdap` lista todas as rotas das 6 fases (≈ 50 endpoints).
- [ ] `npm run build` e `npm run dev` compilam sem warnings.
- [ ] `docker compose exec app php artisan test --filter=Tdap` passa (testes feature por fase).
- [ ] Seed: 2 Prestadores, 4 Caminhões, 1 Ata com 2 Lotes, 1 Cronograma ativo com 2 viagens validadas, 1 Vistoria aprovada.

**DDD / Service Layer SOLID:**
- [ ] `grep -r "Cronograma::query\|Cronograma::find\|Cronograma::where" app/Modules/Tdap --include='*.php' | grep -v "Infrastructure/Persistence"` retorna vazio — nenhuma query de Cronograma fora do `EloquentCronogramaRepository`.
- [ ] Idem para `Prestador`, `Caminhao`, `Ata`, `Lote`, `Vistoria`, `Historico`.
- [ ] Cada Service e cada UseCase recebe **interfaces** no construtor (não classes concretas) — `grep "EloquentRepository" app/Modules/Tdap/Services app/Modules/Tdap/Application` retorna vazio.
- [ ] `TdapServiceProvider::register()` contém um `$this->app->bind(*Interface::class, Eloquent*Repository::class)` para cada entidade.

**Atomic Design:**
- [ ] `grep -rE "<table|<form|<input|<select" resources/js/Pages/Tdap` retorna vazio — Pages não têm HTML estrutural inline.
- [ ] `grep -rE "axios|fetch\(|router\.(get|post|put|delete)" resources/js/Pages/Tdap` retorna vazio — Pages não fazem fetch direto.
- [ ] Cada Page importa **somente** `Templates/*`, `Components/Sections/*` ou `Components/Organisms/*` — `grep "Atoms/\|Molecules/" resources/js/Pages/Tdap` retorna vazio.
- [ ] Pasta `resources/js/Components/Atoms/Tdap/`, `Molecules/Tdap/`, `Sections/Tdap/`, `Organisms/Tdap/`, `resources/js/Templates/Tdap/` existem e contêm arquivos `.vue` reais (não só `.gitkeep`).

**Postgres 18 + PostGIS:**
- [ ] `docker compose exec app php artisan tinker --execute='dump(DB::connection()->getDriverName());'` reporta `pgsql`.
- [ ] `docker compose exec db psql -U sdc -d sdc -c "\d tdap_cronogramas"` mostra colunas `stored_caminhoes` como `jsonb` (não `text`) e `ponto_captacao_geo` como `geography(Point,4326)`.
- [ ] Zero colunas com tipo `enum` USER-DEFINED em `\d tdap_*` (use CHECK constraints).
- [ ] Índices `GIN`/`GIST` confirmados: `docker compose exec db psql -U sdc -d sdc -c "\di tdap_*"` lista `idx_cron_stored_cam` e `idx_cron_geo`.

**Permissões e Layout:**
- [ ] Permissões: `tdap.viewer` consegue listar mas não editar; `tdap.prestador` só vê próprios cronogramas; `tdap.admin` faz tudo.
- [ ] Stub `Almoxarifado` (ex-Tdap) segue funcionando (CRUD vazio mas roteado) — não houve regressão.
- [ ] Item "TDAP — Água Potável" no menu lateral abre o Dashboard da Fase 5.

**Event-Driven Monolith (Fase 6):**
- [ ] `grep -rE "Mail::send|event\(" app/Modules/Tdap/Services` retorna vazio — Services emitem via `$aggregate->recordEvent()`, nunca via `event()` direto. Apenas `OutboxDispatcher` chama `event()`.
- [ ] `grep -r "use App\\\\Modules\\\\Tdap" app/Modules/Decretacoes app/Modules/Pae` retorna vazio — outros módulos **nunca importam código do TDAP**. Comunicação só via Domain Events.
- [ ] Toda classe em `app/Modules/Tdap/Listeners/CrossModule/` estende `IdempotentListener`.
- [ ] `docker compose exec app php artisan tinker --execute="dump(\App\Models\OutboxEvent::whereNull('dispatched_at')->where('occurred_at','<',now()->subSeconds(30))->count());"` reporta `0` em estado estável.
- [ ] Killing chaos test: parar `outbox:dispatch` no meio de um batch, reiniciar; nenhum `processed_events` aparece duplicado (chave primária `(event_id, listener_class)` previne).
- [ ] Tabela `outbox_events` cresce, mas job de archive noturno mantém `< 100k` rows na tabela quente.
- [ ] `php artisan event:replay --listener=AtualizarProjecaoListener --dry-run` reporta total de eventos que seriam reprocessados, sem efeito colateral.

---

## Pontos de Verificação Cruzada com o Legado

Após cada fase, executar comparação contra o legado:

```bash
# legado
grep -rEh "function (index|store|update|destroy|create|edit|show|ativar|prorrogar|export)" \
  C:\Users\x24679188\Documents\Github\sdc\app\Http\Controllers\Tdap\<NomeController>.php

# novo
grep -rEh "function (index|store|update|destroy|create|edit|show|ativar|prorrogar|export)" \
  C:\Users\x24679188\Documents\Github\NewSDC\SDC\app\Modules\Tdap\Controllers\<NomeController>.php
```

A diferença esperada: novo tem **menos** métodos no Controller (lógica empurrada para Service). Nenhuma rota legada pode ficar sem equivalente.

---

## Itens Fora de Escopo (Explícito)

- `app/Http/Controllers/Estoque/` legado — outro domínio, não migrar aqui.
- `app/Http/Controllers/Inventario/` legado e `resources/views/inventario/` — módulo de empréstimo de equipamentos, fora do TDAP.
- `app/Http/Controllers/Tdap/AtaPrestadorController.php` legado — tabela `tdap_ata_prestador` parece duplicar relação já implícita via `Lote.prestador_id`. Será descartada na migração; se aparecer regra de negócio nova, retornar como Fase 6.
- Migração de dados históricos do BD legado para o novo — **não coberto** aqui; depende de ETL separado que será planejado após go-live técnico.
