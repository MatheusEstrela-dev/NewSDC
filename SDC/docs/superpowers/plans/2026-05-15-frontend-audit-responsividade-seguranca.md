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

**Goal:** Eliminar a divergencia de implementacao entre modulos. Definir um **contrato unico de responsividade** que todos os modulos passam a seguir.

**Files:**
- Create: `resources/js/design-system/responsive-contract.md`
- Modify: `resources/js/Components/Organisms/Table/ResponsiveTable.vue` (referencia oficial)
- Modify: 8 Organisms de tabela divergentes
- Create: `scripts/eslint-rules/no-bare-table.js`
- Create: `resources/js/Components/Atoms/ResponsiveDebugOverlay.vue`

### 0.A: Definir o contrato unico

- [ ] **Step 0.A.1: Documentar o contrato**

Criar `resources/js/design-system/responsive-contract.md`:

```
1. BREAKPOINTS (unica fonte: useMobile.js)
   - Mobile: <768px (md)
   - Tablet: 768-1023px (lg)
   - Desktop: >=1024px

2. KPI CARDS
   - <640px: 1 ou 2 colunas (par)
   - 640-1023: 2 ou 3 colunas
   - >=1024: 3 ou 4 colunas
   - Usar <KpiGrid> obrigatoriamente; nunca terminar com card impar solitario

3. TABELAS
   - Desktop: <ResponsiveTable> com todas as colunas
   - Tablet: <ResponsiveTable> com priorityColumns
   - Mobile: TableMobileCard automatico (2-3 dados-chave + drawer detalhes)
   - PROIBIDO: <table> direto sem ResponsiveTable
   - PROIBIDO: overflow-x-auto como unica estrategia mobile

4. FILTROS
   - Desktop: <FilterPanel> sidebar lateral colapsavel
   - Tablet: <FilterPanel> em accordion
   - Mobile: <FilterBottomSheet> via chip "Filtros"

5. PAGE HEADER
   - Sempre via <PageHeader> com titulo, descricao, breadcrumb, acoes
   - Acoes em mobile colapsam em 3-dot se >2

6. FORMULARIOS
   - Mobile: stack vertical, full width, labels acima
   - Desktop: 2-column grid em md+, labels a esquerda opcional

7. ACOES DESTRUTIVAS
   - Desktop: modal centrado
   - Mobile: <ActionSheet> bottom drawer
```

- [ ] **Step 0.A.2: Lint-rule custom**

`scripts/eslint-rules/no-bare-table.js` falha o build se encontrar `<table>` em Pages/Components/Modulo sem ser via `<ResponsiveTable>`.

### 0.B: Auditoria diff entre modulos (catalogo)

- [ ] **Step 0.B.1: Catalogar implementacoes atuais**

Criar `docs/superpowers/audits/2026-05-15-responsive-diff.md`:

| Modulo | Index Page | Table Organism | useMobile | ResponsiveTable | overflow-x-auto | KPI Grid | Status |
|--------|-----------|----------------|-----------|------------------|------------------|----------|--------|
| RAT | RatIndex.vue (69L) | Rat/Table/RatTable.vue | SIM | NAO | - | proprio | parcial |
| PAE | PaeProtocolosIndex.vue (48L) | ? | NAO | NAO | - | proprio | divergente |
| Decretacoes | ProcessoIndex.vue (95L) | Decretacoes/ProcessoTable.vue | NAO | NAO | SIM | proprio | divergente |
| Compdec | OrgaosIndex.vue (355L) | Compdec/CompdecTabs.vue | NAO | NAO | SIM | proprio | bug overflow |
| Demandas | DemandasIndex.vue (36L) | ? | NAO | NAO | ? | proprio | divergente |
| Inventario | InventarioIndex.vue (66L) | Inventario/InventarioTable.vue | NAO | NAO | SIM | proprio | divergente |
| Estoque | (varia) | Estoque/EstoqueTable.vue | NAO | NAO | SIM | proprio | divergente |
| Cisterna | Cisterna/Index.vue | Cisterna/CisternaTable.vue | NAO | NAO | SIM | proprio | divergente |
| Plantao | PlantaoIndex.vue | Plantao/PlantaoTable.vue | NAO | NAO | SIM | proprio | divergente |
| Tdap (9 submods) | Tdap/*/Index.vue | proprios | NAO | NAO | misto | proprio | divergente |
| AjudaHumanitaria | BeneficiarioIndex.vue | proprio | NAO | NAO | ? | proprio | divergente |
| PlanCon | PlanConIndex.vue | proprio | NAO | NAO | ? | proprio | divergente |
| Treinamento | TreinamentoIndex.vue | proprio | NAO | NAO | ? | proprio | divergente |
| Permissions | Users/Index.vue | (inline) | SIM | SIM | - | proprio | OK (referencia) |

**Cada celula divergente vira tarefa nas Fases 8.X.**

- [ ] **Step 0.B.2: Modulo referencia**

Eleger `Permissions/Users` como referencia oficial. Refinar `ResponsiveTable.vue` para suportar todos os casos (paginacao, sort, filtro inline, acoes em linha).

### 0.C: Garantir contrato em runtime

- [ ] **Step 0.C.1: ResponsiveDebugOverlay**

Em desenvolvimento, overlay flutuante mostrando viewport atual, breakpoint ativo e lista de componentes nao-conformes. Ativavel via `?debug=responsive`.

- [ ] **Step 0.C.2: Snapshots Playwright por viewport**

`tests/visual/` com snapshots para 320, 375, 768, 1024, 1440 de todos os Index pages.

### 0.D: Commit fundacao

```bash
git add resources/js/design-system/ docs/superpowers/audits/2026-05-15-responsive-diff.md \
       resources/js/Components/Organisms/Table/ResponsiveTable.vue \
       scripts/eslint-rules/no-bare-table.js \
       resources/js/Components/Atoms/ResponsiveDebugOverlay.vue
git commit -m "feat(design-system): contrato unico de responsividade + catalogo de divergencias"
```

---

## Fase 1: Mapeamento e Baseline

- [ ] **1.1**: Inventario de breakpoints (grep `@media`, `md:`, `lg:`, `xl:`, `2xl:`)
- [ ] **1.2**: Componentes >800 linhas: `Sidebar.vue` 1464L confirmada. Buscar outros.
- [ ] **1.3**: Lighthouse baseline em 4 paginas x 4 viewports (375, 768, 1024, 1440). Salvar em `docs/superpowers/audits/lighthouse/`.
- [ ] **1.4**: Snapshot inicial Playwright (`--update-snapshots`).
- [ ] **1.5**: Commit baseline.

---

## Fase 2: Fix Critico Tablet Gap

**Bug:** Viewport `[768, 1024)` recebe `md:ml-20` (80px) mas Sidebar so visivel se `isSidebarOpen`. Resultado: faixa vazia.

- [ ] **2.1**: Decidir comportamento em tablet (recomendado: rail collapsed visivel em vez de drawer)
- [ ] **2.2**: Aplicar correcao em `AuthenticatedLayout.vue:70`
- [ ] **2.3**: Atualizar TopBar hamburguer condicional
- [ ] **2.4**: Replicar em `SidebarOnlyLayout.vue`
- [ ] **2.5**: Teste Playwright especifico em 895px (gap detection)
- [ ] **2.6**: Commit

---

## Fase 3: Consolidacao de Breakpoints e Design Tokens

- [ ] **3.1**: Tornar `BREAKPOINTS` de `useMobile.js` exportavel e referenciado via CSS custom properties
- [ ] **3.2**: Substituir 34 `@media` inline por classes Tailwind (priorizar BottomNavigation, Sidebar, TopBar, Dropdown, FormField)
- [ ] **3.3**: Tokens de touch target (`touch-min: 44px`, `touch-rec: 48px`)
- [ ] **3.4**: Regras de overflow para `ResponsiveTable` e `TableMobileCard`
- [ ] **3.5**: Commit

---

## Fase 4: Bug Sweep Multi-Viewport

**Matriz:**

| Nome | Largura | Altura |
|------|---------|--------|
| mobile-s | 320 | 568 |
| mobile-m | 375 | 812 |
| mobile-l | 414 | 896 |
| tablet-p | 768 | 1024 |
| tablet-l | 1024 | 768 |
| laptop | 1366 | 768 |
| desktop | 1920 | 1080 |
| ultrawide | 2560 | 1440 |

- [ ] **4.1**: Suite Playwright parametrizada (cada pagina x cada viewport): zero console.error, zero overflow horizontal, snapshot visual.
- [ ] **4.2**: Corrigir overflow-x detectados (`min-w-0`, `truncate`, `overflow-x-auto` quando inevitavel).
- [ ] **4.3**: KPI grids consistentes via `<KpiGrid>` (Fase 0).
- [ ] **4.4**: ApexCharts com config `responsive: [{ breakpoint: 768, ...}]`.
- [ ] **4.5**: Modal/Drawer 100vw em mobile, `max-w-*` em desktop.
- [ ] **4.6**: Commit

---

## Fase 5: Refactor Leve da Sidebar (1464 LOC)

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
- [ ] **6.A.2**: `tests/Feature/Security/SqlInjectionTest.php` com payloads (`'; DROP TABLE--`, etc)

### B. XSS
- [ ] **6.B.1**: Auditar 11 arquivos com `v-html` (Tdap/Viagens/Pendentes.vue, Processos/Index.vue, Vistorias/Index.vue, etc)
- [ ] **6.B.2**: Substituir por `<SafeHtml>` com DOMPurify
- [ ] **6.B.3**: Validar CSP no `SecurityHeaders.php` (sem `unsafe-inline` em prod)

### C. CSRF
- [ ] **6.C.1**: Justificar cada rota em `VerifyCsrfToken.$except`
- [ ] **6.C.2**: Confirmar token em requisicoes axios manuais (`bootstrap.js`)

### D. IDOR
- [ ] **6.D.1**: Grep `function (show|edit|update|destroy)\(.*\$id` em `app/Modules`. Verificar Policy/Gate ou filtro tenant/owner em cada
- [ ] **6.D.2**: `tests/Feature/Security/AuthorizationTest.php`

### E. Outros
- [ ] **6.E.1**: Mass assignment (Models sem `$fillable`)
- [ ] **6.E.2**: Open redirect (`redirect($request->input(...))`)
- [ ] **6.E.3**: SSRF (`Http::get($urlDoRequest)`)
- [ ] **6.E.4**: `unserialize(` com input externo
- [ ] **6.E.5**: Storage publico vazado
- [ ] **6.E.6**: Validar `HealthCheckController` `exec` legitimo + protegido

### F. Relatorio
- [ ] **6.F.1**: `security-report.md` com severidade por finding
- [ ] **6.F.2**: Commit

---

## Fase 7: Auditoria de Dependencias

- [ ] **7.1**: `composer audit` + `composer outdated --direct`
- [ ] **7.2**: `npm audit` + `npm outdated`
- [ ] **7.3**: Verificar versoes pilares (Laravel 12, Vue 3.5+, Inertia 2.2+, Vite 5, Tailwind 3)
- [ ] **7.4**: Pacotes abandonados (`composer show | grep abandon`)
- [ ] **7.5**: Confirmar lockfiles versionados
- [ ] **7.6**: Commit

---

## Fase 8: UX e Responsividade por Modulo (Web + Mobile)

**Goal:** Cada modulo migra para o contrato da Fase 0. Tarefas cobrem Desktop, Tablet e Mobile.

### 8.0: Componentes base compartilhados

- [ ] **8.0.1: KpiGrid responsivo** (Web + Mobile) - auto-distribute para evitar card solitario
- [ ] **8.0.2: FilterPanel (Web) + FilterBottomSheet (Mobile)** - mesma API, renders diferentes
- [ ] **8.0.3: ResponsiveTable refactor** - auto-switch table/cards por viewport
- [ ] **8.0.4: Fix skeleton race condition** - `usePageLoading.js` reseta em `router.on('finish')`, timeout 10s
- [ ] **8.0.5: PageHeader unificado** (Web/Mobile)
- [ ] **8.0.6: Commit base**

### 8.1: RAT
**Web:**
- [ ] Tabela usa `<ResponsiveTable>` (substituir RatTable.vue atual)
- [ ] Filtros laterais em desktop
- [ ] Form de criacao 2-col em desktop

**Mobile:**
- [ ] RatCard com tap-area >=44px e badge status
- [ ] RatTabs vira `<MobileWizard>` step-by-step
- [ ] Upload foto via camera (`capture="environment"`)
- [ ] Geolocalizacao `navigator.geolocation`
- [ ] Offline mode via Dexie/IndexedDB

### 8.2: PAE
**Web (screenshot fornecida):**
- [ ] KPI 4 cards: `grid-cols-2 md:grid-cols-2 lg:grid-cols-4`
- [ ] Tabela `<ResponsiveTable>` com colunas: Protocolo, Empreendedor, Analista, Datas, Situacao, Acoes
- [ ] Filtros laterais colapsaveis em desktop
- [ ] Toggle Grade/Tabela funcional em ambos viewports

**Mobile:**
- [ ] 4 KPIs em 2x2 grid
- [ ] Lista protocolos como cards (`#13.05.2026.016`, status chip, datas, acoes-icone)
- [ ] Filtros via `<FilterBottomSheet>`
- [ ] Acoes (ver/imprimir/editar/vencimento/deletar) via `<ActionSheet>` em tap longo
- [ ] Tabs Total/Historico/Vencidos/CCPAE como ChipFilter horizontal scrollavel

### 8.3: Decretacoes
**Web:**
- [ ] Tabela `<ResponsiveTable>`
- [ ] 5 KPIs em `grid-cols-2 md:grid-cols-3 lg:grid-cols-5`
- [ ] Filtros Avancados + COBRADE em sidebar colapsavel

**Mobile:**
- [ ] 5 KPIs em `grid-cols-2` com ultimo span-2 OU carousel horizontal
- [ ] Filtros em accordion mobile
- [ ] Processos como cards com chip ECP/SE
- [ ] Mapa de municipios fullscreen com `tap: true` Leaflet

### 8.4: Compdec/Orgaos (bug confirmado nas screenshots)
**Web:**
- [ ] Tabela `<ResponsiveTable>`
- [ ] OrgaoShow tabs Geral/Capacidades/Prefeitura

**Mobile (fix overflow critico):**
- [ ] Tabela `CODIGO/NOME/TIPO/MUNICIPIO/STATUS/USUARIOS/ACOES` vira cards (`NOME + chip TIPO + badge STATUS + icone-acao`)
- [ ] KPIs Total/COMPDECs/REDECs/Ativos em 2x2
- [ ] Tabs em `<TabsMobile>` scrollavel horizontal

### 8.5: Demandas
**Web:**
- [ ] Tabela `<ResponsiveTable>` com indicador SLA por cor

**Mobile:**
- [ ] Cards com SLA color-coded
- [ ] Swipe direita = atender / esquerda = arquivar
- [ ] PullToRefresh confirmado ativo

### 8.6: TDAP (9 submodulos)
**Web:**
- [ ] Cada submodulo (Viagens, Processos, Vistorias, Cronogramas, Lotes, Atas, Historicos, Caminhoes, Prestadores) migra para `<ResponsiveTable>`

**Mobile:**
- [ ] `<TdapHub>` grid 3x3 de cards-icone
- [ ] Viagens pendentes com botoes Aprovar/Reprovar inline
- [ ] Vistoria com upload sequencial + geotag

### 8.7: Ajuda Humanitaria
**Web:**
- [ ] Tabela `<ResponsiveTable>` para beneficiarios
- [ ] Cadastro familia em form 2-col

**Mobile:**
- [ ] Cards avatar + nome + chip status
- [ ] Cadastro em `<MobileWizard>`
- [ ] Estoque com search sticky + scan codigo

### 8.8: Inventario
**Web:** Tabela `<ResponsiveTable>` com thumbnails
**Mobile:** Cards thumb + nome + qtd; scan QR/barcode

### 8.9: Plantao Diario
**Web:** Tabela `<ResponsiveTable>` + grafico tempo
**Mobile:** Timeline vertical + FAB "+ Novo Evento" fixo bottom-right

### 8.10: Inmet/Meteorologia
**Web:** Mapa com painel lateral de metricas
**Mobile:** Mapa fullscreen + card legenda colapsavel

### 8.11: PlanCon/Cisternas/Treinamento
- [ ] PlanCon: revisar `v-html` em `PlanConLinkCard.vue` (XSS Fase 6)
- [ ] Cisternas: lista geo + mapa colapsavel topo
- [ ] Treinamento: cards de cursos com badge progresso
- [ ] Todos migram para `<ResponsiveTable>`

### 8.12: BottomNavigation (mobile)
- [ ] Tabs: Visao Geral / DEMANDAS / RAT / PAE / Mais...
- [ ] Indicador visual + badge notificacoes
- [ ] "Mais..." abre `<ActionSheet>` com modulos restantes

### 8.13: Commit final
- [ ] Snapshot Playwright em 375 e 414px para cada modulo
- [ ] Lighthouse Mobile `--preset=mobile` >=85

---

## Fase 9: NativePHP (App Nativo)

### 9.A: Avaliacao e POC Desktop
- [ ] **9.A.1**: Decisao arquitetural (PWA vs NativePHP vs Capacitor) - recomendado NativePHP
- [ ] **9.A.2**: `composer require nativephp/electron`
- [ ] **9.A.3**: POC Desktop - validar login, Inertia, Vite, SQLite local
- [ ] **9.A.4**: Decidir SQLite local + sync OU thin client

### 9.B: iOS (requer macOS + Apple Dev)
- [ ] **9.B.1**: `composer require nativephp/ios`
- [ ] **9.B.2**: Safe areas, status bar dark, app icon, splash
- [ ] **9.B.3**: Permissoes Info.plist (camera, location, photos)
- [ ] **9.B.4**: Build TestFlight

### 9.C: Android
- [ ] **9.C.1**: `composer require nativephp/android`
- [ ] **9.C.2**: Permissoes Manifest (camera, location, storage)
- [ ] **9.C.3**: Adaptive icon, splash, tema material
- [ ] **9.C.4**: Build APK assinado

### 9.D: APIs Nativas
- [ ] **9.D.1**: Notificacoes (`Native\Laravel\Facades\Notification`)
- [ ] **9.D.2**: GPS background para Plantao/RAT campo
- [ ] **9.D.3**: Camera nativa robusta
- [ ] **9.D.4**: Filesystem (PDFs locais)
- [ ] **9.D.5**: Worker sync offline

### 9.E: Distribuicao
- [ ] **9.E.1**: `docs/superpowers/nativephp/build-pipeline.md` (Win/Mac/Linux/iOS/Android)
- [ ] **9.E.2**: Auto-updater Electron + EAS Update mobile
- [ ] **9.E.3**: Commit final

---

## Entregaveis Finais

| Artefato | Caminho |
|----------|---------|
| Contrato de responsividade | `resources/js/design-system/responsive-contract.md` |
| Catalogo de divergencias | `docs/superpowers/audits/2026-05-15-responsive-diff.md` |
| Baseline visual | `docs/superpowers/audits/2026-05-15-baseline.md` |
| Relatorio seguranca | `docs/superpowers/audits/2026-05-15-security-report.md` |
| Relatorio dependencias | `docs/superpowers/audits/2026-05-15-deps-report.md` |
| Suite E2E multi-viewport | `tests/e2e/viewports.spec.js` |
| Snapshots visuais | `tests/visual/` |
| Testes seguranca | `tests/Feature/Security/{SqlInjection,Authorization}Test.php` |
| ESLint rule custom | `scripts/eslint-rules/no-bare-table.js` |
| Fix gap tablet | commits Fase 2 |
| Sidebar refatorada | commits Fase 5 |
| Componentes base | `Components/Atoms/ResponsiveDebugOverlay.vue`, `Molecules/Mobile/{ActionSheet,FilterBottomSheet,KpiGrid,PageHeader}.vue` |
| Apps nativos | Electron (.exe/.dmg/.AppImage) + iOS (.ipa) + Android (.apk) |
| Docs NativePHP | `docs/superpowers/nativephp/{ios-build,android-build,build-pipeline}.md` |

---

## Criterios de Aceitacao

### Padronizacao (Fase 0)
- [ ] Todos os 13 modulos usam `<ResponsiveTable>`, `<KpiGrid>`, `<FilterPanel/BottomSheet>`, `<PageHeader>` do design-system
- [ ] Zero `<table>` direto fora de `<ResponsiveTable>` (ESLint rule passa)
- [ ] `responsive-diff.md` mostra todas as celulas como "OK"

### Responsividade (Fases 2, 3, 4, 8)
- [ ] Screenshot dashboard em 895px nao mostra faixa preta
- [ ] Suite Playwright passa nos 8 viewports
- [ ] Zero overflow horizontal em qualquer viewport >=320px
- [ ] Tabelas em mobile usam cards automaticamente (Compdec/Orgaos bug resolvido)
- [ ] KPI grids nunca terminam com card solitario
- [ ] Touch targets >=44x44px em mobile
- [ ] Skeleton loader nunca trava
- [ ] Diminuir a tela em qualquer modulo gera transicoes consistentes e identicas (test visual diff entre modulos no mesmo viewport)

### Seguranca (Fases 6, 7)
- [ ] `composer audit` 0 advisories ou justificados
- [ ] `npm audit --audit-level=high` 0 vulnerabilities
- [ ] Todos `v-html` mapeados/migrados para `<SafeHtml>`
- [ ] Todos `whereRaw` usam bindings
- [ ] Todos Controllers com `$id` tem Policy/Gate

### Refactor (Fases 5)
- [ ] Sidebar.vue <800 linhas

### Performance (Fase 1)
- [ ] Lighthouse Performance Desktop >=80
- [ ] Lighthouse Performance Mobile >=85

### NativePHP (Fase 9)
- [ ] Build Electron executa e abre janela funcional
- [ ] APK Android assinado gerado
- [ ] iOS roda em simulador Xcode (TestFlight opcional)

---

## Riscos e Mitigacoes

| Risco | Probabilidade | Mitigacao |
|-------|---------------|-----------|
| Refactor Sidebar quebra navegacao | Media | Snapshots Playwright + diff visual obrigatorio |
| `composer update` quebra Laravel/Octane | Media | Update incremental, `php artisan test` apos cada |
| Remocao `md:ml-20` quebra desktop | Baixa | Playwright em 1280/1440/1920 |
| DOMPurify aumenta bundle | Baixa | `defineAsyncComponent` no `<SafeHtml>` |
| Tempo >91h | Alta | Sprints separados; Fase 9 projeto a parte |
| NativePHP beta | Alta | Validar POC Fase 9.A antes de iOS/Android. Fallback PWA existente |
| Build iOS exige macOS + $99/ano | Media | Priorizar Android + Electron primeiro |
| Sync offline causa conflitos | Media | Definir "last-write-wins" ou CRDT |
| Refactor tabelas->cards quebra bulk actions | Media | Toggle view=table/cards em mobile |
| Migracao de 13 modulos para contrato unico e custosa | Alta | Fase 0 cria automacao (lint + debug overlay); Fase 8 paraleliza por modulo |

---

## Ordem de Execucao Recomendada

| Ordem | Fase | Horas | Categoria |
|-------|------|-------|-----------|
| 1 | Fase 0 | 6h | Fundacao |
| 2 | Fase 1 | 4h | Baseline |
| 3 | Fase 2 | 3h | Fix critico |
| 4 | Fase 8.0 | 4h | Componentes base |
| 5 | Fase 6 | 12h | Seguranca |
| 6 | Fase 7 | 2h | Deps (paralelo Fase 6) |
| 7 | Fase 4 | 6h | Bug sweep |
| 8 | Fase 8.1-8.12 | 16h | Por modulo |
| 9 | Fase 3 | 6h | Consolidacao |
| 10 | Fase 5 | 8h | Refactor Sidebar |
| 11 | Fase 9.A | 4h | POC NativePHP |
| 12 | Fase 9.C | 6h | Android |
| 13 | Fase 9.B | 8h | iOS |
| 14 | Fase 9.D-E | 6h | APIs + distribuicao |

**Total: 91h**
- Obrigatorias (0, 1, 2, 4, 6, 7, 8.0): 37h
- Por modulo Web+Mobile (8.1-8.12): 16h
- Refactors recomendados (3, 5): 14h
- NativePHP (9): 24h

**Sprints sugeridos:**
- **Sprint 1 (1 semana)**: Fases 0, 1, 2, 8.0, 6, 7 - fundacao + estabilidade + seguranca (31h)
- **Sprint 2 (1 semana)**: Fases 4, 8.1-8.6 - mobile/web dos modulos criticos (RAT, PAE, Compdec, Decretacoes, Demandas, TDAP)
- **Sprint 3 (1 semana)**: Fases 8.7-8.12, 3, 5 - modulos restantes + refactor
- **Sprint 4 (1-2 semanas)**: Fase 9 - NativePHP (paralelizavel: Android+Electron em Windows, iOS em macOS)
