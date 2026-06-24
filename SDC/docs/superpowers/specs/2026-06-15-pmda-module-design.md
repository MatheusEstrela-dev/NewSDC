# PMDA — Design do Módulo (Plano Municipal de Defesa Agropecuária)

> Spec de design para portar o módulo **PMDA** do legado (`sdc` + `gestaocedec`) para o NewSDC,
> seguindo o padrão DDD + Service Layer + Atomic Design já consolidado no plano canônico
> `docs/superpowers/plans/2026-05-11-tdap-migration.md`. O TDAP consome o PMDA aprovado;
> este módulo é a origem do planejamento que alimenta os cronogramas de transporte de água.

- **Data:** 2026-06-15
- **Branch:** dev2
- **Banco alvo:** PostgreSQL (conexão `localhost:5433`, database `sdc`)
- **Decisões já tomadas (brainstorming):**
  - PMDA (a sigla "PWDA" do pedido inicial foi typo).
  - Escopo: módulo PMDA completo (todo o ciclo).
  - Máquina de estados: **consolidar** os dois campos paralelos do legado (`status` + `estado`) em **um único enum**.
  - Tabelas padronizadas com prefixo `pmda_*` (reaproveitando `pmda_pontos` já existente).
  - Arquitetura: **DDD personalizado** com fluxo `Request → DTO → Controller → Service → Model`
    (estrutura leve igual a `app/Modules/Pae` e `app/Modules/Decretacoes`; **sem** camada
    Domain/Infrastructure/Repository).
- **Fluxo de branches:** toda implementação sai de um branch `feat/<escopo>` criado **a partir da
  última `dev`** (ex.: `feat/pmda-fundacao` ← `dev`). Cada fase fecha em seu `feat/` e mergeia de volta.

---

## 1. Contexto e Objetivo

O **PMDA — Plano Municipal de Defesa Agropecuária** é o instrumento pelo qual um município
formaliza, junto à CEDEC-MG, seu plano de resposta à seca: comunidades a serem atendidas com
água potável, pontos de captação, representantes locais, dados do município/COMPDEC e ações de
resposta. Após aprovação pela CEDEC, o PMDA habilita a geração de **cronogramas TDAP**
(transporte de água).

**Sistemas legados mapeados:**

- `sdc` (Laravel legado): `app/Http/Controllers/Pmda/*`, `app/Models/Pmda/*`, helpers
  `pmda_status.php` / `mah_helper_function.php`. Tabela principal `pip_pmda`.
- `gestaocedec` (PHP procedural): `mod_pipa/` com `Classe.Pmda.php` (~1700 linhas) concentrando
  toda a regra de negócio (criação, cópia, análise, aprovação, mensagens, snapshots).

**Objetivo:** reescrever a regra de negócio do PMDA em `app/Modules/Pmda/`, em fases incrementais
que entregam valor isoladamente, cada fase cobrindo DB + Backend + Frontend + testes de ponta a ponta.

**Não-objetivos:** migração de dados históricos do MySQL legado (será tratada em plano de ETL
separado); reescrita do TDAP (já planejado/implementado).

---

## 2. Regras de Negócio Mapeadas (legado)

### 2.1 Entidades

| Domínio | Legado `sdc` | Legado `gestaocedec` | NewSDC (alvo) |
|---|---|---|---|
| Plano | `pip_pmda` | `pip_pmda` | `pmda_planos` |
| Comunidade do plano | `pip_pmda_comun` | `pip_pmda_comun` | `pmda_comunidades` |
| Representante | `pip_pmda_representante` | `pip_representante` | `pmda_representantes` |
| Ponto de captação | `pip_pmda_ponto` | `pip_ponto_cap` | `pmda_pontos` (já existe) |
| Vínculo plano↔ponto | `pip_pmda_pmdaponto` | — | `pmda_plano_ponto` |
| Mensagem | `pip_pmda_msg` | `pip_pmda_msg` | `pmda_mensagens` |
| Anexo | `pip_pmda_anexo` | `pip_anexo` | `pmda_anexos` (Spatie Media) |
| Histórico de alteração | `pip_pmda_alteracao` | `pip_pmda_alteracao` | `pmda_alteracoes` |
| Comentário interno | `pip_pmda_coment` | `pip_pmda_coment` | `pmda_comentarios` |

### 2.2 Campos do plano (`pmda_planos`)

Núcleo (origem `pip_pmda`):
`id`, `protocolo`, `municipio_id` (FK), `status` (enum — ver 2.3), `data` (criação),
`acoes` (text), `qtd_caminhao` (int), `pop_at_municipio` (int), `resp_homolog`, `dt_analise`,
`dt_ultima_alteracao`, `data_aprov`, `resp_estado`, `dt_estado`, flags `pedido_altera` (bool) e
`alterar_com` (bool), `created_by`, `updated_by`, `timestamps`, `softDeletes`.

Dados ISS / Município / COMPDEC (origem das abas do `edit.blade.php` / `pmda.php`):
`cobra_iss` (bool), `num_lei_iss`, `aliquota_iss` (decimal), `resp_cob_iss`,
`nome_prefeito`, `tel_prefeitura`, `tel_prefeito`, `cel_prefeito`, `endereco`, `bairro`, `cep`,
`email_prefeitura`, `populacao`, `pop_rural`, `area`. (Confirmar quais migram para `pmda_planos`
e quais derivam da entidade `municipios` já existente — ver §6 questões abertas.)

### 2.3 Máquina de estados unificada (`PmdaStatus`)

O legado usa **dois campos paralelos** (`status` 0-9 e `estado` textual "Analista Cedec" /
"Encerrado Atendimento"). O NewSDC consolida num **único enum** (`varchar` + `CHECK`):

| Enum novo | Legado (status / estado) | Quem transiciona | Efeito colateral |
|---|---|---|---|
| `RASCUNHO` | 0 Em Edição | COMPDEC | — |
| `COMPLETO` | 1 Completo | sistema (auto) | exige ≥1 comunidade e 3 representantes por comunidade |
| `EM_ANALISE` | 2 / "Analista Cedec" | COMPDEC envia; CEDEC recebe | grava `resp_homolog`, `dt_analise` |
| `APROVADO` | 4 Aprovado | CEDEC | grava `data_aprov`; habilita cronograma TDAP |
| `ATENDIDO` | 7 / "Encerrado Atendimento" | CEDEC | grava `dt_estado`, `resp_estado` |
| `ARQUIVADO` | 3 Arquivado | CEDEC | read-only |
| `ANULADO` | 5 Anulado (+ 6 Nulo migra p/ cá) | COMPDEC | terminal |
| `CANCELADO` | 8 Cancelado | CEDEC | terminal |
| `ENCERRADO` | 9 Encerrado | CEDEC | terminal |

**Transições válidas:**

```
RASCUNHO  ──auto──>  COMPLETO  ──enviar──>  EM_ANALISE
EM_ANALISE ──aprovar──> APROVADO
EM_ANALISE ──pedir alteração──> RASCUNHO   (seta pedido_altera = true)
EM_ANALISE ──arquivar──> ARQUIVADO
APROVADO   ──atender──> ATENDIDO
(qualquer não-terminal) ──> ANULADO | CANCELADO
```

`COMPLETO → RASCUNHO` ocorre automaticamente se a validação (comunidades/representantes) deixar
de ser satisfeita. Estados terminais: `ATENDIDO`, `ARQUIVADO`, `ANULADO`, `CANCELADO`, `ENCERRADO`.

### 2.4 Regra de cópia (duplicação)

Origem: `PmdaController::create()` (sdc) e `Pmda::copiaPmda()` (gestaocedec).

Um PMDA pode ser **copiado** somente quando:
1. `data` (criação) **> 2021-04-03**; **e**
2. `status ∉ {RASCUNHO, COMPLETO, EM_ANALISE, APROVADO}`.

A cópia cria novo plano em `RASCUNHO` com `data = now()`, duplicando comunidades e representantes.
Restrição adicional do legado: não criar novo PMDA se já existir um em `RASCUNHO` para o mesmo município.

### 2.5 Protocolo

Formato: `{id}{YYYYMMDD}` (ex.: plano `1739` criado em 15/06/2026 → `173920260615`).
No legado é gerado no momento da análise; no NewSDC será gerado/persistido no backend na criação
(coluna `protocolo` em `pmda_planos`), garantindo unicidade.

### 2.6 Comunidades e Representantes

- Cada **comunidade** do plano: `comunidade_id`, `municipio_id`, `ponto_id` (captação),
  `latitude`, `longitude`, `trecho_pav` (km), `trecho_n_pav` (km), `pop_atendida`.
- Validação forte: **3 representantes por comunidade** (obrigatório para chegar a `COMPLETO`).
- Uma comunidade não pode estar em dois planos com status ativo simultaneamente
  (legado: rejeita se já em plano com status 0,1,2,4,7).
- Pós-aprovação, edição de comunidades só com flag `alterar_com = true`; mudanças gravam snapshot
  em histórico (`pmda_alteracoes`).

### 2.7 Mensagens, Comentários, Anexos, Histórico

- **Mensagens** (`pmda_mensagens`): comunicação CEDEC↔município. Campos: `usuario_id`,
  `municipio_id`, `msg`, `status` (0 não lida / 1 lida), `pmda_plano_id`, `dt_envio`, `dt_leitura`,
  `protocolo`, `tp_mensagem`.
- **Comentários** (`pmda_comentarios`): notas internas livres por plano.
- **Anexos** (`pmda_anexos`): documentos (ofício da prefeitura). Usar **Spatie Media Library**
  (já no stack), não coluna `arquivo` string.
- **Histórico** (`pmda_alteracoes`): auditoria de edições — `editor`, `alteracao`, `dt_alteracao`.
  Preferir o trait de auditoria já existente no NewSDC quando aplicável.

---

## 3. Arquitetura Alvo (DDD personalizado: Request → DTO → Controller → Service → Model)

Segue a estrutura **real** dos módulos de produção `app/Modules/Pae/` e `app/Modules/Decretacoes/`
(estrutura leve — **sem** as camadas `Domain/Infrastructure/Repositories`, que só Tdap/Rat
adotaram por necessidade de Event-Driven). Fluxo de uma requisição:

```
HTTP → FormRequest (valida + authorize via slug)
     → DTO (readonly, fromArray)        ← contrato de entrada
     → Controller (fino, orquestra)
     → Service (regra de negócio)
     → Model (Eloquent anêmico)
     → Resource (serializa) → Inertia/JSON
```

```
app/Modules/Pmda/
├── Controllers/              ← finos: FormRequest → DTO → Service → Inertia/Resource
├── Requests/                 ← FormRequests (authorize via slug + rules + prepareForValidation)
├── DTOs/                     ← readonly, fromArray()/toArray() (snake_case ↔ camelCase)
├── Services/                 ← Service Layer SOLID; 1 responsabilidade por classe; estende BaseService
├── Models/                   ← Eloquent anêmico (fillable, casts, relations, scopes triviais)
├── Resources/                ← JSON Resources (serialização)
├── Enums/                    ← PmdaStatus (backed enum + transições)
├── Observers/                ← hooks Eloquent (gerar protocolo, recalcular status)
├── Mail/                     ← notificações (aprovação, pedido de alteração)  [Fase 5]
└── PmdaServiceProvider.php   ← registra Services (singletons) + Observers
```

**Regras (DRY/SOLID):**
- Controller **não** instancia Model nem contém regra; só orquestra `Request → DTO → Service`.
- **Service** é o único lugar com regra de negócio e acesso ao Model (via Eloquent direto, como
  PAE/Decretações — não há camada Repository neste módulo). Estende `app/Modules/Shared/BaseService.php`
  (helpers `paginate`, `applyFilters`, `applySearch`).
- **Model anêmico**: sem regra de negócio; `creating` event (Observer ou `booted()`) só para
  defaults (protocolo, uuid).
- **DTO** é o contrato entre Request e Service — Service nunca recebe array bruto do Request.

**Frontend (Atomic Design estrito):**

```
resources/js/
├── Components/{Atoms,Molecules,Sections,Organisms}/Pmda/
├── Templates/Pmda/               ← PmdaCrudTemplate, PmdaDetailTemplate
├── Pages/Pmda/                   ← Inertia (sem HTML estrutural inline)
└── composables/pmda/             ← usePmda, useComunidades, useAnalise (TanStack Vue Query)
```

**Banco (Postgres):** `varchar` + `CHECK` para enums (não ENUM nativo); `softDeletes()` TZ-aware;
FKs `ON DELETE RESTRICT/CASCADE` conforme integridade; índices em campos filtráveis e em `status`.

**Kernel `.claude`:** criar `.claude/pmda/main.py` com contrato `MODULE` (name, purpose, triggers,
read_order, actions) para que `python .claude/kernel.py --detect "...pmda..."` passe a casar o
módulo e oriente sessões futuras (atende o pedido de "inicializar o kernel para direcionamento").

---

## 4. Permissões (slugs `pmda.*`)

Bloco `'PMDA'` para `config/permissions.php`, no **formato real** já usado por PAE/TDAP/RAT
(pares explícitos `'acao' => 'modulo.recurso.acao'`). Cada slug é consumível por
`->middleware('can:<slug>')` na rota e por `authorize()` nos FormRequests:

```php
'PMDA' => [
    // PMDA - Plano Municipal de Defesa Agropecuaria
    'Dashboard' => [
        'view' => 'pmda.dashboard.view',
    ],
    'Planos' => [
        'view'   => 'pmda.planos.view',
        'create' => 'pmda.planos.create',
        'edit'   => 'pmda.planos.edit',
        'delete' => 'pmda.planos.delete',
        'copiar' => 'pmda.planos.copiar',
        'export' => 'pmda.planos.export',
    ],
    'Comunidades' => [
        'view'   => 'pmda.comunidades.view',
        'create' => 'pmda.comunidades.create',
        'edit'   => 'pmda.comunidades.edit',
        'delete' => 'pmda.comunidades.delete',
    ],
    'Representantes' => [
        'view'   => 'pmda.representantes.view',
        'create' => 'pmda.representantes.create',
        'edit'   => 'pmda.representantes.edit',
        'delete' => 'pmda.representantes.delete',
    ],
    'Pontos' => [
        'view'   => 'pmda.pontos.view',
        'create' => 'pmda.pontos.create',
        'edit'   => 'pmda.pontos.edit',
        'delete' => 'pmda.pontos.delete',
    ],
    'Analise' => [
        'view'            => 'pmda.analise.view',
        'enviar'          => 'pmda.analise.enviar',
        'aprovar'         => 'pmda.analise.aprovar',
        'arquivar'        => 'pmda.analise.arquivar',
        'pedir_alteracao' => 'pmda.analise.pedir_alteracao',
    ],
    'Mensagens' => [
        'view'   => 'pmda.mensagens.view',
        'create' => 'pmda.mensagens.create',
    ],
    'Anexos' => [
        'view'   => 'pmda.anexos.view',
        'create' => 'pmda.anexos.create',
        'delete' => 'pmda.anexos.delete',
    ],
],
```

**Mapa slug → fase / consumidor** (já amarrado ao módulo, conforme pedido):

| Slug | Fase | Rota / FormRequest que consome |
|---|---|---|
| `pmda.planos.{view,create,edit,delete}` | 1 | `PmdaPlanoController` + `Store/UpdatePmdaPlanoRequest` |
| `pmda.planos.copiar` | 1 | `PmdaCopiaService` (rota `pmda.planos.copiar`) |
| `pmda.comunidades.*` | 2 | `ComunidadeController` + requests |
| `pmda.representantes.*` | 2 | `RepresentanteController` + requests |
| `pmda.pontos.*` | 3 | `PlanoPontoController` (vínculo com `pmda_pontos`) |
| `pmda.analise.{enviar,aprovar,arquivar,pedir_alteracao}` | 5 | `PmdaAnaliseService` (transições) + painel CEDEC |
| `pmda.mensagens.*` | 4 | `MensagemController` |
| `pmda.anexos.*` | 4 | `AnexoController` (Spatie Media) |
| `pmda.dashboard.view` | 5 | `PmdaDashboardController` |

Atores: **COMPDEC** (município — `planos`, `comunidades`, `representantes`, `mensagens`, `anexos`,
`analise.enviar`) e **CEDEC** (estado — `analise.{aprovar,arquivar,pedir_alteracao}`, `dashboard`).
Super-admin com bypass total via `BasePolicy::before()`.

---

## 5. Fases (sequenciais; cada fase fecha valor e mergeia antes da próxima)

| Fase | DB | Backend | Frontend | Testes |
|---|---|---|---|---|
| **0 — Fundação** | nenhuma | esqueleto DDD, `PmdaServiceProvider`, `routes/modules/pmda.php`, slugs `pmda.*`, `.claude/pmda/main.py` | pastas Atomic + Templates base + item de menu | route:list, build |
| **1 — PMDA Core** | `pmda_planos`, enum `PmdaStatus` | CRUD + máquina de estados + protocolo + regra de cópia + listagem/filtros | Index/Create/Edit/Show, StatusBadge | Feature: criação, cópia (regras), transições válidas/inválidas |
| **2 — Comunidades + Representantes** | `pmda_comunidades`, `pmda_representantes` | vínculos, validação 3-reps → `COMPLETO`, bloqueio de comunidade duplicada | ComunidadesTable, RepresentantesForm | Feature: regra 3-reps, auto-COMPLETO, duplicidade |
| **3 — Pontos de Captação + dados ISS/Município** | `pmda_plano_ponto` (reusa `pmda_pontos`) | vínculo plano↔ponto, abas ISS/Município/COMPDEC | PontosSection, IssForm, MunicipioForm + mapa (Leaflet) | Feature: vínculo, validação ISS |
| **4 — Mensagens, Anexos, Histórico** | `pmda_mensagens`, `pmda_anexos`, `pmda_alteracoes`, `pmda_comentarios` | MensagemService, anexos Spatie, auditoria | MensagensThread, AnexosUploader, Timeline | Feature: enviar/ler msg, upload, log de alteração |
| **5 — Análise/Aprovação CEDEC + Dashboard + Integração TDAP** | — | `PmdaAnaliseService` (aprovar/arquivar/pedir alteração), e-mails, painel CEDEC, query "PMDAs aprovados" p/ TDAP | Dashboard, PainelAnalise, HomologacaoForm | Feature: fluxo aprovação ponta-a-ponta; PMDA aprovado aparece p/ cronograma |

---

## 6. Questões abertas (resolver no início das fases)

1. **Dados ISS/Município/COMPDEC**: quais colunas migram para `pmda_planos` e quais já existem na
   entidade `municipios` do NewSDC (evitar duplicação)? — confirmar contra o schema `localhost:5433/sdc`.
2. **`pmda_pontos` existente**: validar o schema atual da tabela (criada para o TDAP) antes de
   reaproveitar na Fase 3; ajustar se faltar coluna.
3. **Trait de auditoria**: confirmar o equivalente ao `LogsModelChanges` no NewSDC para
   `pmda_alteracoes` (Fase 4).
4. **Migração de dados**: ETL `pip_*` (MySQL) → `pmda_*` (Postgres) é plano separado; mapear
   `status`/`estado` legados → enum unificado conforme tabela §2.3.

---

## 7. Critérios de Aceite (globais)

- Máquina de estados implementada como enum + transições explícitas; transição inválida lança
  exceção de domínio e é coberta por teste.
- Regra de cópia (data > 2021-04-03 + status permitido + sem rascunho duplicado) testada.
- Protocolo único gerado no backend.
- Regra "3 representantes por comunidade" bloqueia avanço para `COMPLETO`.
- CRUD completo navegável pela UI seguindo Atomic Design (Pages sem HTML estrutural).
- `php artisan test --filter=Pmda` verde ao fim de cada fase.
- `.claude/kernel.py --detect` reconhece o módulo PMDA.
