# Auditoria Frontend NewSDC: Responsividade, Bugs, Mobile e Infiltracoes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auditar a SPA NewSDC (Vue3 + Inertia + Tailwind), padronizar a responsividade entre TODOS os modulos (atualmente cada modulo reinventa), corrigir bugs visiveis nas screenshots (gap tablet, overflow horizontal, skeleton travado, KPI grids quebrados), melhorar UX mobile por modulo, varrer backend + frontend por OWASP Top 10 e dependencias, e avaliar empacotamento como app nativo via NativePHP.

**Architecture:** Auditoria por camadas (Layout > Design System > Componentes > Composables > Controllers > Requests) com fixes pontuais e um **contrato unico de responsividade** que todos os modulos passam a seguir. Sem reescrita ampla. Reaproveitar `useMobile.js` como fonte unica de breakpoints.

**Tech Stack:** Laravel 12, Vue 3, Inertia.js v2, Tailwind 3, Vite 5, TanStack Query, Playwright (E2E), Octane/FrankenPHP, MySQL.

**Escopo confirmado:**
- Alvo: NewSDC apenas (`C:\Users\x24679188\Documents\Github\NewSDC\SDC`)
- Seguranca: OWASP Top 10 + audit de dependencias (composer/npm)
- Profundidade: Abrangente (refactor leve)
- **Mobile-first por modulo**: cada modulo (RAT, PAE, Decretacoes, Compdec, Demandas, TDAP, Ajuda Humanitaria, Inventario, PlanCon, Plantao, Inmet, Cisternas, Treinamento) recebe revisao Web + Mobile dedicada
- **Responsividade padronizada**: contrato unico para todos os modulos, eliminando divergencias atuais
- **NativePHP**: avaliar empacotamento como app nativo (Electron desktop, iOS, Android)

---

## Evidencia Inicial

### Desktop/Tablet (screenshots originais)
- Dashboard em viewport ~895px (entre `md`=768 e `lg`=1024): faixa preta de ~80px a esquerda do conteudo, sidebar nao visivel.
- Causa: `AuthenticatedLayout.vue:70` aplica `md:ml-20` mas Sidebar.vue so abre em tablet quando `isSidebarOpen===true`. Margem reservada para sidebar invisivel.

### Mobile (screenshots novas)
- **Compdec mobile**: skeleton loader persistente apos navegacao - race condition no `isPageLoading`.
- **Orgaos mobile**: tabela com overflow horizontal cortando colunas (`CODIGO -> IPIO`, `STATUS`, `USUARIOS`, `ACOES` parcialmente fora). Icones de acao deslocados para fora do card.
- **Decretacoes mobile**: 5 cards KPI em grid 2 colunas ficam mal distribuidos (ultimo card solitario).
- **Sidebar mobile drawer**: funciona bem, mas logo "SDC MG" sobreposta a horario do sistema (z-index/safe-area).
- **PAE Painel Gerencial mobile**: cards em coluna unica desperdicam densidade.

### Achado critico de padronizacao (auditoria executada na codebase)
- `ResponsiveTable.vue` existe em `Components/Organisms/Table/` mas e usado **apenas em 2 paginas** (Admin/Permissions/Users e Permissions).
- `useMobile()` e usado em **apenas 1 tabela de modulo**: `RatTable.vue`. As demais 8 tabelas (Pae, Decretacoes, Compdec, Demandas, Inventario, Estoque, Cisterna, Plantao, Tdap) **NAO usam mobile breakpoint** - apenas envolvem `<table>` em `overflow-x-auto`, gerando scroll horizontal que corta colunas no mobile (efeito visto na screenshot Compdec/Orgaos).
- 15 arquivos com `<table>` em Pages, mas so 7 protegem overflow.
- Index pages dos modulos (Decretacoes 95L, Demandas 36L, Pae 48L, Rat 69L, Inventario 66L) sao thin wrappers - logica responsiva delegada aos Organisms, mas cada Organism tem implementacao propria divergente.
- **Diagnostico**: nao existe padrao unico de responsividade entre modulos. Cada modulo reinventa ou ignora. Fase 0 enderecca isto.

---

## Fase 0: Padronizacao da Responsividade entre Modulos (FUNDACAO)

Status: **CONCLUIDA em 2026-05-18** (commit `b0f68b6c`).

**Entregaveis:**
- `resources/js/design-system/responsive-contract.md` (14 regras obrigatorias)
- `docs/superpowers/audits/2026-05-15-responsive-diff.md` (catalogo de divergencias)
- `resources/js/Components/Organisms/Table/ResponsiveTable.vue` refinado com `data-responsive="true"` e prop `priorityColumns`
- `resources/js/Components/Atoms/ResponsiveDebugOverlay.vue` (Atoms, ativado via `?debug=responsive`)
- `scripts/check-responsive-contract.js` + `responsive-contract.baseline.json` (23 violacoes baselined)
- npm scripts: `lint:responsive`, `lint:responsive:baseline`
- Integracao no `AuthenticatedLayout.vue`

---

## Fase 1: Mapeamento e Baseline

Status: **CONCLUIDA em 2026-05-18** (commit `e12f5870`).

**Entregaveis:** `docs/superpowers/audits/2026-05-18-baseline.md`
- 22 arquivos com `@media` inline (alvos Fase 3)
- 1 componente >800 LOC (Sidebar 1464L)
- 12 componentes 400-800 LOC
- Procedimento de captura Lighthouse + Playwright documentado

---

## Fase 2: Fix Critico Tablet Gap

Status: **CONCLUIDA em 2026-05-18**.

**Bug original:** Viewport `[768, 1024)` recebia `md:ml-20` (80px) mas Sidebar.vue so passava texto/icones quando `isSidebarOpen===true`. Em viewport ~895px aparecia faixa preta de ~80px.

**Solucao implementada:** Adicionado computed `effectivelyCollapsed` em Sidebar.vue que retorna `true` em tablet (sem drawer aberto) ou em desktop quando usuario colapsa, e `false` em tablet/mobile com drawer aberto. Substitui 45 referencias inline de `isCollapsed` por `effectivelyCollapsed` no template (mantendo `isCollapsed` apenas no toggle button v-if="isDesktop" e funcoes do script). Layouts (`AuthenticatedLayout`, `SidebarOnlyLayout`) ja aplicavam `md:ml-20` corretamente - bug estava so na Sidebar.

- [x] **2.1**: Decidir comportamento em tablet (rail collapsed visivel)
- [x] **2.2**: Adicionar `effectivelyCollapsed` computed em Sidebar.vue
- [x] **2.3**: Substituir 45 refs de `isCollapsed` no template por `effectivelyCollapsed`
- [x] **2.4**: AuthenticatedLayout/SidebarOnlyLayout sem alteracoes (md:ml-20 ja correto)
- [x] **2.5**: `tests/e2e/responsive-tablet-gap.spec.js` (768/895/1023px + smoke /login)
- [x] **2.6**: Commit

---

## Fase 3: Consolidacao de Breakpoints e Design Tokens

- [ ] **3.1**: Tornar `BREAKPOINTS` de `useMobile.js` exportavel + CSS custom properties
- [ ] **3.2**: Substituir 34 `@media` inline por classes Tailwind
- [ ] **3.3**: Tokens de touch target (`touch-min: 44px`, `touch-rec: 48px`)
- [ ] **3.4**: Regras de overflow para `ResponsiveTable` e `TableMobileCard`
- [ ] **3.5**: Commit

---

## Fase 4: Bug Sweep Multi-Viewport

**Matriz:** 320, 375, 414, 768, 1024, 1366, 1920, 2560.

- [ ] **4.1**: Suite Playwright parametrizada (pagina x viewport): zero console.error, zero overflow horizontal, snapshot visual
- [ ] **4.2**: Corrigir overflow-x detectados
- [ ] **4.3**: KPI grids consistentes via `<KpiGrid>` (Fase 0)
- [ ] **4.4**: ApexCharts com config `responsive: [{ breakpoint: 768, ...}]`
- [ ] **4.5**: Modal/Drawer 100vw em mobile, `max-w-*` em desktop
- [ ] **4.6**: Commit

---

## Fase 5: Refactor Leve da Sidebar (1464 LOC)

Status parcial: **CSS modularizado em 2026-05-18** - Sidebar.vue reduzida para 923L (-37%) movendo CSS para `Sidebar.styles.css`.

- [x] **5.0**: Extrair CSS para `Sidebar.styles.css` (548 LOC) com regions comentadas
- [ ] **5.1**: Extrair permissoes para `useSidebarNavigation.js`
- [ ] **5.2**: `SidebarSection.vue` (title + slot)
- [ ] **5.3**: `SidebarSubmenu.vue`
- [ ] **5.4**: Validar zero regressao via diff visual
- [ ] **5.5**: Commit

---

## Fase 6: Auditoria OWASP Top 10

**Findings preliminares:**
- 24 ocorrencias de `DB::raw|whereRaw|orderByRaw|selectRaw` em 10 arquivos
- 11 arquivos com `v-html`
- 1 ocorrencia de `eval|exec|system` (`HealthCheckController.php`)

### A. SQL Injection
- [ ] **6.A.1**: Revisar 10 arquivos. Prioritarios: `Decretacoes/ProcessoQueryService.php` (9 ocorr), `AjudaHumanitaria/Models/Abrigo.php` (4), `BeneficiarioService.php` (2)
- [ ] **6.A.2**: `tests/Feature/Security/SqlInjectionTest.php`

### B. XSS
- [ ] **6.B.1**: Auditar 11 arquivos com `v-html` (Tdap/*, PlanConLinkCard, Admin/Users)
- [ ] **6.B.2**: Substituir por `<SafeHtml>` com DOMPurify
- [ ] **6.B.3**: Validar CSP no `SecurityHeaders.php`

### C. CSRF
- [ ] **6.C.1**: Justificar cada rota em `VerifyCsrfToken.$except`
- [ ] **6.C.2**: Confirmar token em axios manuais

### D. IDOR
- [ ] **6.D.1**: Grep Controllers com `$id`, verificar Policy/Gate
- [ ] **6.D.2**: `tests/Feature/Security/AuthorizationTest.php`

### E. Outros
- [ ] **6.E.1**: Mass assignment
- [ ] **6.E.2**: Open redirect
- [ ] **6.E.3**: SSRF
- [ ] **6.E.4**: `unserialize(` com input externo
- [ ] **6.E.5**: Storage publico vazado
- [ ] **6.E.6**: Validar `HealthCheckController` `exec`

### F. Relatorio
- [ ] **6.F.1**: `security-report.md` por severidade
- [ ] **6.F.2**: Commit

---

## Fase 7: Auditoria de Dependencias

- [ ] **7.1**: `composer audit` + `composer outdated --direct`
- [ ] **7.2**: `npm audit` + `npm outdated`
- [ ] **7.3**: Versoes pilares (Laravel 12, Vue 3.5+, Inertia 2.2+, Vite 5, Tailwind 3)
- [ ] **7.4**: Pacotes abandonados
- [ ] **7.5**: Lockfiles versionados
- [ ] **7.6**: Commit

---

## Fase 8: UX e Responsividade por Modulo (Web + Mobile)

**Goal:** Cada modulo migra para o contrato Fase 0. Tarefas cobrem Desktop, Tablet, Mobile.

### 8.0: Componentes base compartilhados
- [ ] **8.0.1**: KpiGrid responsivo (auto-distribute)
- [ ] **8.0.2**: FilterPanel (Web) + FilterBottomSheet (Mobile)
- [ ] **8.0.3**: ResponsiveTable refactor (auto-switch table/cards)
- [ ] **8.0.4**: Fix skeleton race condition em `usePageLoading.js`
- [ ] **8.0.5**: PageHeader unificado
- [ ] **8.0.6**: Commit base

### 8.1-8.12: Modulos
| Subfase | Modulo | Tarefas Web | Tarefas Mobile |
|---------|--------|-------------|----------------|
| 8.1 | RAT | Migrar `RatTable.vue` para `<ResponsiveTable>` | RatCard tap-area; MobileWizard; camera; geo; offline |
| 8.2 | PAE | KPI 4 cards; `<ResponsiveTable>`; filtros laterais | 4 KPIs 2x2; cards protocolo; FilterBottomSheet; ActionSheet |
| 8.3 | Decretacoes | `<ResponsiveTable>`; 5 KPIs grid; filtros sidebar | 5 KPIs span-2; accordion; cards ECP/SE; Leaflet `tap: true` |
| 8.4 | Compdec/Orgaos | `<ResponsiveTable>`; tabs Geral/Capacidades/Prefeitura | Fix overflow critico; KPI 2x2; TabsMobile scroll |
| 8.5 | Demandas | `<ResponsiveTable>` SLA color | Cards SLA; swipe atender/arquivar |
| 8.6 | TDAP (9 submods) | Cada submodulo migra | TdapHub 3x3 grid; pendentes aprovar/reprovar; vistoria foto seq |
| 8.7 | AjudaHumanitaria | `<ResponsiveTable>`; cadastro 2-col | Cards avatar; MobileWizard cadastro; scan codigo |
| 8.8 | Inventario | `<ResponsiveTable>` thumbnails | Cards thumb; scan QR/barcode |
| 8.9 | Plantao | `<ResponsiveTable>` + grafico | Timeline + FAB |
| 8.10 | Inmet | Mapa + painel lateral | Mapa fullscreen + legenda colapsavel |
| 8.11 | PlanCon/Cisternas/Treinamento | Migrar todos para `<ResponsiveTable>` | Cards apropriados por modulo |
| 8.12 | BottomNavigation | - | Visao Geral / DEMANDAS / RAT / PAE / Mais; ActionSheet "Mais" |

### 8.13: Validacao final
- [ ] Snapshot Playwright em 375 e 414px para cada modulo
- [ ] Lighthouse Mobile `--preset=mobile` >= 85

---

## Fase 9: NativePHP (App Nativo)

### 9.A: Avaliacao e POC Desktop
- [ ] **9.A.1**: Decidir entre PWA, NativePHP, Capacitor
- [ ] **9.A.2**: `composer require nativephp/electron`
- [ ] **9.A.3**: POC Desktop
- [ ] **9.A.4**: Decidir SQLite local + sync vs thin client

### 9.B: iOS (requer macOS + Apple Dev)
- [ ] **9.B.1**: `composer require nativephp/ios`
- [ ] **9.B.2**: Safe areas, status bar, app icon
- [ ] **9.B.3**: Permissoes Info.plist
- [ ] **9.B.4**: Build TestFlight

### 9.C: Android
- [ ] **9.C.1**: `composer require nativephp/android`
- [ ] **9.C.2**: Permissoes Manifest
- [ ] **9.C.3**: Adaptive icon, splash, tema material
- [ ] **9.C.4**: Build APK assinado

### 9.D: APIs Nativas
- [ ] **9.D.1**: Notificacoes
- [ ] **9.D.2**: GPS background
- [ ] **9.D.3**: Camera nativa robusta
- [ ] **9.D.4**: Filesystem
- [ ] **9.D.5**: Worker sync offline

### 9.E: Distribuicao
- [ ] **9.E.1**: `docs/superpowers/nativephp/build-pipeline.md`
- [ ] **9.E.2**: Auto-updater Electron + EAS Update
- [ ] **9.E.3**: Commit final

---

## Entregaveis Finais

| Artefato | Caminho |
|----------|---------|
| Contrato responsividade | `resources/js/design-system/responsive-contract.md` |
| Catalogo divergencias | `docs/superpowers/audits/2026-05-15-responsive-diff.md` |
| Baseline visual | `docs/superpowers/audits/2026-05-18-baseline.md` |
| Sidebar CSS extraido | `resources/js/Components/Sidebar.styles.css` |
| Relatorio seguranca | `docs/superpowers/audits/2026-05-15-security-report.md` |
| Relatorio dependencias | `docs/superpowers/audits/2026-05-15-deps-report.md` |
| Suite E2E multi-viewport | `tests/e2e/viewports.spec.js` |
| Testes seguranca | `tests/Feature/Security/{SqlInjection,Authorization}Test.php` |
| Lint custom | `scripts/check-responsive-contract.js` |
| Componentes base | `Atoms/ResponsiveDebugOverlay.vue`, `Molecules/Mobile/*` |
| Apps nativos | Electron (.exe/.dmg/.AppImage) + iOS (.ipa) + Android (.apk) |
| Docs NativePHP | `docs/superpowers/nativephp/*.md` |

---

## Criterios de Aceitacao

### Padronizacao (Fase 0)
- [ ] Todos 13 modulos usam componentes do design-system
- [ ] Zero `<table>` direto fora de `<ResponsiveTable>` (lint passa sem baseline)
- [ ] `responsive-diff.md` com todas celulas "OK"

### Responsividade (Fases 2, 3, 4, 8)
- [ ] Screenshot dashboard em 895px sem faixa preta
- [ ] Playwright passa nos 8 viewports
- [ ] Zero overflow horizontal em qualquer viewport >=320px
- [ ] Tabelas mobile usam cards automaticamente
- [ ] KPI grids nunca terminam com card solitario
- [ ] Touch targets >=44x44px em mobile
- [ ] Skeleton loader nao trava
- [ ] Transicoes consistentes entre modulos no mesmo viewport

### Seguranca (Fases 6, 7)
- [ ] `composer audit` 0 advisories
- [ ] `npm audit --audit-level=high` 0 vulnerabilities
- [ ] Todos `v-html` mapeados/migrados
- [ ] Todos `whereRaw` usam bindings
- [ ] Controllers com `$id` tem Policy/Gate

### Refactor (Fase 5)
- [x] Sidebar.vue reduzida (concluido: 1464 -> 923 LOC, CSS extraido)
- [ ] Sidebar.vue <800 linhas com extracao de SidebarSection/Submenu

### Performance (Fase 1)
- [ ] Lighthouse Desktop >=80
- [ ] Lighthouse Mobile >=85

### NativePHP (Fase 9)
- [ ] Build Electron funcional
- [ ] APK Android assinado
- [ ] iOS roda em simulador

---

## Riscos e Mitigacoes

| Risco | Probabilidade | Mitigacao |
|-------|---------------|-----------|
| Refactor Sidebar quebra navegacao | Media | Snapshots Playwright + diff visual obrigatorio |
| `composer update` quebra Laravel/Octane | Media | Update incremental, `php artisan test` apos cada |
| Remocao `md:ml-20` quebra desktop | Baixa | Playwright em 1280/1440/1920 |
| DOMPurify aumenta bundle | Baixa | `defineAsyncComponent` no `<SafeHtml>` |
| NativePHP beta | Alta | Validar POC Fase 9.A antes de iOS/Android |
| Build iOS exige macOS + $99/ano | Media | Priorizar Android + Electron primeiro |
| Sync offline causa conflitos | Media | Definir "last-write-wins" ou CRDT |
| Migracao de 13 modulos custosa | Alta | Fase 0 automatiza lint; Fase 8 paraleliza |
| Rat.vue com markers de conflito commitados em `dev` | Confirmado | Resolver em PR separado; nao bloqueia Sidebar/contract |

---

## Ordem de Execucao Recomendada

| Ordem | Fase | Horas | Status |
|-------|------|-------|--------|
| 1 | Fase 0 | 6h | CONCLUIDA (b0f68b6c) |
| 2 | Fase 1 | 4h | CONCLUIDA (e12f5870) |
| 3 | Fase 5.0 (CSS extract) | 1h | CONCLUIDA |
| 4 | Fase 2 | 3h | em andamento |
| 5 | Fase 8.0 | 4h | pendente |
| 6 | Fase 6 | 12h | pendente |
| 7 | Fase 7 | 2h | pendente (paralelo a Fase 6) |
| 8 | Fase 4 | 6h | pendente |
| 9 | Fase 8.1-8.12 | 16h | pendente |
| 10 | Fase 3 | 6h | pendente |
| 11 | Fase 5.1-5.5 | 7h | pendente |
| 12 | Fase 9.A | 4h | pendente |
| 13 | Fase 9.C | 6h | pendente |
| 14 | Fase 9.B | 8h | pendente |
| 15 | Fase 9.D-E | 6h | pendente |

**Total: 91h** (concluido: 11h, restante: 80h)

**Sprints sugeridos:**
- Sprint 1 (em curso): Fases 0, 1, 2, 5.0, 8.0, 6, 7
- Sprint 2: Fases 4, 8.1-8.6 (RAT, PAE, Compdec, Decretacoes, Demandas, TDAP)
- Sprint 3: Fases 8.7-8.12, 3, 5.1-5.5
- Sprint 4: Fase 9 (NativePHP)
