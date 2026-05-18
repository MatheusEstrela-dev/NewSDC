<<<<<<< Updated upstream
<<<<<<< Updated upstream
# Auditoria Frontend NewSDC: Responsividade, Bugs, Mobile e Infiltracoes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auditar a SPA NewSDC (Vue3 + Inertia + Tailwind), padronizar a responsividade entre TODOS os modulos (atualmente cada modulo reinventa), corrigir bugs visiveis nas screenshots (gap tablet, overflow horizontal, skeleton travado, KPI grids quebrados), melhorar UX mobile por modulo, varrer backend + frontend por OWASP Top 10 e dependencias, e avaliar empacotamento como app nativo via NativePHP.

**Architecture:** Auditoria por camadas (Layout > Design System > Componentes > Composables > Controllers > Requests) com fixes pontuais e um **contrato unico de responsividade** que todos os modulos passam a seguir. Sem reescrita ampla. Reaproveitar `useMobile.js` como fonte unica de breakpoints.
=======
=======
>>>>>>> Stashed changes
# Auditoria Frontend NewSDC: Responsividade, Bugs e Infiltracoes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auditar a SPA NewSDC (Vue3 + Inertia + Tailwind), corrigir bugs de responsividade visiveis (gap da sidebar em viewport tablet), consolidar breakpoints, e varrer o backend Laravel + frontend Vue por vulnerabilidades OWASP Top 10 e dependencias com CVE.

**Architecture:** Auditoria por camadas (Layout > Componentes > Composables > Controllers > Requests) com fixes pontuais. Sem reescrita ampla. Reaproveitar `useMobile.js` (breakpoints Tailwind oficiais) como fonte unica de verdade.
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

**Tech Stack:** Laravel 12, Vue 3, Inertia.js v2, Tailwind 3, Vite 5, TanStack Query, Playwright (E2E), Octane/FrankenPHP, MySQL.

**Escopo confirmado:**
- Alvo: NewSDC apenas (`C:\Users\x24679188\Documents\Github\NewSDC\SDC`)
- Seguranca: OWASP Top 10 + audit de dependencias (composer/npm)
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
- Profundidade: Abrangente (refactor leve, sem DDD)
- **Mobile-first por modulo**: cada modulo (RAT, PAE, Decretacoes, Compdec, Demandas, TDAP, Ajuda Humanitaria, Inventario, PlanCon, Plantao, Inmet, Cisternas, Treinamento) recebe revisao mobile dedicada
- **NativePHP**: avaliar empacotamento como app nativo (desktop via electron, iOS/Android via nativephp/ios e nativephp/android)

**Evidencia inicial (screenshots fornecidas):**

*Desktop/Tablet:*
- Dashboard em viewport ~895px (entre `md`=768 e `lg`=1024): faixa preta de ~80px a esquerda do conteudo, sidebar nao visivel.
- Causa: `AuthenticatedLayout.vue:70` aplica `md:ml-20` mas Sidebar.vue so abre em tablet quando `isSidebarOpen===true`. Resultado: margem reservada para uma sidebar invisivel.

*Achado critico de padronizacao (auditoria na codebase):*
- `ResponsiveTable.vue` existe em `Components/Organisms/Table/` mas e usado **apenas em 2 paginas** (Admin/Permissions/Users e Permissions).
- `useMobile()` e usado em **apenas 1 tabela de modulo**: `RatTable.vue`. As demais 8 tabelas de modulo (Pae, Decretacoes, Compdec, Demandas, Inventario, Estoque, Cisterna, Plantao, Tdap) **NAO usam mobile breakpoint** — apenas envolvem `<table>` em `overflow-x-auto`, gerando scroll horizontal que corta colunas no mobile (efeito visto na screenshot do Compdec/Orgaos).
- 15 arquivos com `<table>` em Pages, mas so 7 protegem overflow.
- `Decretacoes/ProcessoIndex.vue` (95 linhas), `Demandas/DemandasIndex.vue` (36 linhas), `PaeProtocolosIndex.vue` (48 linhas), `RatIndex.vue` (69 linhas), `InventarioIndex.vue` (66 linhas) **nao tem nenhum breakpoint explicito** — toda logica responsiva esta delegada aos Organisms (boa pratica), mas cada Organism tem implementacao propria divergente.
- **Diagnostico**: nao existe padrao unico de responsividade entre modulos. Cada modulo reinventa ou ignora.

*Mobile (screenshots novas):*
- **Compdec mobile**: skeleton loader (placeholders cinza) parece travado/persistente apos navegacao, indicando race condition no `isPageLoading` ou ausencia de transicao de loaded.
- **Orgaos mobile**: tabela com overflow horizontal cortando colunas (`CODIGO -> IPIO`, `STATUS`, `USUARIOS`, `ACOES` parcialmente fora da tela). Icones de acao tambem deslocados para fora do card.
- **Decretacoes mobile**: cards KPI em grid 2 colunas (`Total Eventos`, `Registros`, `Decretacoes`, `Municipios Atingidos`, `Vigentes`) ficam apertados com 5 cards mal distribuidos.
- **Sidebar mobile drawer**: funciona bem (overlay + drawer 280px), mas logo "SDC MG" sobreposta a horario do sistema (z-index/safe-area).
- **PAE Painel Gerencial mobile**: cards de KPI em coluna unica (OK), mas falta densidade aproveitavel (apenas 1 card visivel por dobra inicial).
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 0: Padronizacao da Responsividade entre Modulos (FUNDACAO)

**Goal:** Eliminar a divergencia de implementacao entre modulos. Definir um **contrato unico de responsividade** que todos os modulos passam a seguir.

**Files:**
<<<<<<< Updated upstream
<<<<<<< Updated upstream
- Create: `resources/js/design-system/responsive-contract.md`
- Modify: `resources/js/Components/Organisms/Table/ResponsiveTable.vue` (referencia oficial)
- Modify: 8 Organisms de tabela divergentes
- Create: `scripts/eslint-rules/no-bare-table.js`
- Create: `resources/js/Components/Atoms/ResponsiveDebugOverlay.vue`

### 0.A: Definir o contrato unico

- [ ] **Step 0.A.1: Documentar o contrato**

Criar `resources/js/design-system/responsive-contract.md`:
=======
=======
>>>>>>> Stashed changes
- Create: `resources/js/design-system/responsive-contract.md` (documentacao do contrato)
- Modify: `resources/js/Components/Organisms/Table/ResponsiveTable.vue` (tornar referencia oficial)
- Modify: 8 Organisms de tabela divergentes (ver lista)
- Modify: paginas Index dos modulos para usar contrato

### 0.A: Definir o contrato unico

- [ ] **Step 0.A.1: Documentar o contrato de responsividade**

Criar `resources/js/design-system/responsive-contract.md` com regras OBRIGATORIAS:
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

```
1. BREAKPOINTS (unica fonte: useMobile.js)
   - Mobile: <768px (md)
   - Tablet: 768-1023px (lg)
   - Desktop: >=1024px

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
2. KPI CARDS (todos os modulos)
   - <640px: 1 ou 2 colunas (par)
   - 640-1023: 2 ou 3 colunas
   - >=1024: 3 ou 4 colunas
   - Usar componente <KpiGrid> obrigatoriamente

3. TABELAS (todos os modulos)
   - Desktop: <ResponsiveTable> com todas as colunas
   - Tablet: <ResponsiveTable> com colunas prioritarias
   - Mobile: cards automaticos (TableMobileCard) com 2-3 dados-chave
   - PROIBIDO: <table> direto sem ResponsiveTable
   - PROIBIDO: overflow-x-auto como unica estrategia mobile

4. FILTROS (todos os modulos)
   - Desktop: <FilterPanel> lateral colapsavel
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
   - Tablet: <FilterPanel> em accordion
   - Mobile: <FilterBottomSheet> via chip "Filtros"

5. PAGE HEADER
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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

=======
=======
>>>>>>> Stashed changes
   - Usar <PageHeader> com titulo, descricao, breadcrumb, acoes
   - Acoes em mobile colapsam em 3-dot menu se >2

6. FORMULARIOS
   - Mobile: stack vertical, full width, labels acima
   - Desktop: 2-column grid quando >=md, labels a esquerda opcional

7. ACOES DESTRUTIVAS
   - Desktop: modal centrado de confirmacao
   - Mobile: <ActionSheet> bottom drawer
```

Este documento e a referencia obrigatoria. Toda nova feature deve respeitar.

- [ ] **Step 0.A.2: Criar lint-rule custom**

Adicionar regra ESLint (`scripts/eslint-rules/no-bare-table.js`) que falha o build se encontrar `<table>` em Pages/Components/Pae|Rat|Decretacoes|etc sem ser via `<ResponsiveTable>`.

### 0.B: Auditoria diff entre modulos (catalogo)

- [ ] **Step 0.B.1: Catalogar implementacoes atuais por modulo**

Criar `docs/superpowers/audits/2026-05-15-responsive-diff.md` com tabela:

| Modulo | Index Page | Table Organism | Usa useMobile | Usa ResponsiveTable | Overflow-x-auto | KPI Grid | Filtros | Status |
|--------|-----------|----------------|----------------|----------------------|------------------|----------|---------|--------|
| RAT | RatIndex.vue (69L) | Rat/Table/RatTable.vue | SIM | NAO | - | proprio | proprio | parcial |
| PAE | PaeProtocolosIndex.vue (48L) | Pae/PaeProtocolosTable.vue? | NAO | NAO | - | proprio | proprio | divergente |
| Decretacoes | ProcessoIndex.vue (95L) | Decretacoes/ProcessoTable.vue | NAO | NAO | SIM | proprio | proprio | divergente |
| Compdec | OrgaosIndex.vue (355L) | Compdec/CompdecTabs.vue | NAO | NAO | SIM | proprio | proprio | bug overflow |
| Demandas | DemandasIndex.vue (36L) | ? | NAO | NAO | ? | proprio | ? | divergente |
| Inventario | InventarioIndex.vue (66L) | Inventario/InventarioTable.vue | NAO | NAO | SIM | proprio | proprio | divergente |
| Estoque | (varia) | Estoque/EstoqueTable.vue | NAO | NAO | SIM | proprio | proprio | divergente |
| Cisterna | Cisterna/Index.vue | Cisterna/CisternaTable.vue | NAO | NAO | SIM | proprio | proprio | divergente |
| Plantao | PlantaoIndex.vue | Plantao/PlantaoTable.vue | NAO | NAO | SIM | proprio | proprio | divergente |
| Tdap (9 submods) | Tdap/*/Index.vue | proprios | NAO | NAO | misto | proprio | proprio | divergente |
| AjudaHumanitaria | BeneficiarioIndex.vue | proprio | NAO | NAO | ? | proprio | proprio | divergente |
| PlanCon | PlanConIndex.vue | proprio | NAO | NAO | ? | proprio | proprio | divergente |
| Treinamento | TreinamentoIndex.vue | proprio | NAO | NAO | ? | proprio | proprio | divergente |
| Permissions | Users/Index.vue | (inline) | SIM | SIM | - | proprio | proprio | OK (referencia) |

**Cada celula vermelha vira uma tarefa nas Fases 8.X.**

- [ ] **Step 0.B.2: Definir "modulo referencia"**

Eleger `Permissions/Users` como referencia oficial (ja usa `ResponsiveTable` + `useMobile`). Documentar como template.

Refinar `ResponsiveTable.vue` se necessario para suportar todos os casos de uso (paginacao, sort, filtro inline, acoes em linha).

### 0.C: Garantir contrato em runtime (DevTool)

- [ ] **Step 0.C.1: Componente debug ResponsiveOverlay**

Em desenvolvimento (NODE_ENV !== production), exibir um overlay flutuante (canto inferior direito) mostrando:
- Viewport atual (`375px - mobile`, `1024px - desktop`, etc)
- Breakpoint ativo
- Lista de componentes nao-conformes na pagina (qualquer `<table>` sem `data-responsive="true"`)

Ativavel via `?debug=responsive` na URL.

- [ ] **Step 0.C.2: Storybook por viewport**

Configurar Storybook 7+ com viewport addon. Cada componente novo deve ter stories para: 320, 375, 768, 1024, 1440.

(Se Storybook for muito custoso, alternativa: `tests/visual/` com snapshots Playwright por viewport.)

### 0.D: Commit fundacao

- [ ] **Step 0.D.1: Commit**

<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
```bash
git add resources/js/design-system/ docs/superpowers/audits/2026-05-15-responsive-diff.md \
       resources/js/Components/Organisms/Table/ResponsiveTable.vue \
       scripts/eslint-rules/no-bare-table.js \
       resources/js/Components/Atoms/ResponsiveDebugOverlay.vue
<<<<<<< Updated upstream
<<<<<<< Updated upstream
git commit -m "feat(design-system): contrato unico de responsividade + catalogo de divergencias"
```

=======
=======
>>>>>>> Stashed changes
git commit -m "feat(design-system): define contrato unico de responsividade e cataloga divergencias entre modulos"
```

**Apos esta fase, todas as fases seguintes (incluindo 8.X) tem o contrato como guia. Cada modulo na Fase 8 vira esta fase de divergente -> conforme.**

<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
---

## Fase 1: Mapeamento e Baseline

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
**Files:**
- Read: `resources/js/Layouts/AuthenticatedLayout.vue`
- Read: `resources/js/Components/Sidebar.vue` (1464 linhas)
- Read: `resources/js/Components/TopBar.vue`
- Read: `resources/js/Composables/mobile/useMobile.js`
- Read: `tailwind.config.js`
- Create: `docs/superpowers/audits/2026-05-15-baseline.md`

- [ ] **Step 1.1: Inventario de breakpoints**

Coletar grep de `@media`, `md:`, `lg:`, `xl:`, `2xl:` e gerar tabela: arquivo > quantidade > breakpoints usados.

Comando:
```bash
cd "C:/Users/x24679188/Documents/Github/NewSDC/SDC"
rg "@media \(max-width|@media \(min-width" resources/js -c
rg "(sm|md|lg|xl|2xl):(ml-|mr-|p-|m-|w-|grid-cols|flex|block|hidden)" resources/js -c | sort -t: -k2 -n -r | head -20
```

Saida esperada: top 10 arquivos com maior densidade de breakpoints inline.

- [ ] **Step 1.2: Inventario de componentes >800 linhas**

```bash
find resources/js -name "*.vue" -exec wc -l {} + | sort -n -r | head -15
```

Marcar como alvos de refactor leve (extrair sub-componentes). Sidebar.vue (1464 LOC) ja confirmado.

- [ ] **Step 1.3: Baseline de performance Lighthouse**

Rodar Lighthouse em 4 viewports nas 4 paginas das screenshots:
- `/dashboard`, `/pae`, `/decretacoes`, `/admin/permissions/users`
- Viewports: 375px (mobile), 768px (tablet portrait), 1024px (tablet landscape), 1440px (desktop)

Salvar JSON em `docs/superpowers/audits/lighthouse/`.

- [ ] **Step 1.4: Snapshot inicial Playwright**

Executar `npx playwright test --update-snapshots` para fixar baseline visual nos viewports problematicos.

- [ ] **Step 1.5: Commit baseline**

```bash
git add docs/superpowers/audits/
git commit -m "audit: baseline frontend responsividade e performance"
```

---

## Fase 2: Fix Critico de Responsividade (Tablet Gap)

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue:70-77`
- Modify: `resources/js/Components/Sidebar.vue` (regras CSS responsivas)
- Modify: `resources/js/Layouts/SidebarOnlyLayout.vue` (mesmo padrao)

**Bug confirmado:** Em viewport `[768px, 1024px)`, o conteudo principal recebe `md:ml-20` (80px) mas a Sidebar so e renderizada/visivel se `isSidebarOpen===true`. Resultado: 80px de fundo `bg-slate-50/950` aparecem como faixa vazia.

- [ ] **Step 2.1: Decidir comportamento em tablet**

Decisao recomendada: **Sidebar sempre visivel em modo `collapsed` (rail de 80px) em tablet**, ao inves de overlay sob demanda. Alinha com `md:ml-20`.

Alternativa: remover `md:ml-20`, manter `ml-0` em tablet, sidebar continua como drawer. Escolher uma e documentar.

- [ ] **Step 2.2: Aplicar correcao no Layout**

Se opcao A (rail collapsed em tablet):

```vue
<!-- AuthenticatedLayout.vue:70 -->
<div
  class="flex-1 flex flex-col min-h-screen ml-0 md:ml-20 lg:ml-[280px]"
  :class="{ 'lg:!ml-20': sidebarCollapsed }"
>
```

Modificar Sidebar.vue para forcar `is-collapsed` quando `isTablet === true` (sem drawer overlay), e remover dependencia de `isSidebarOpen` para visibilidade em tablet.

Se opcao B (drawer puro em tablet):

```vue
<div class="flex-1 flex flex-col min-h-screen ml-0 lg:ml-[280px]"
  :class="{ 'lg:!ml-20': sidebarCollapsed }"
>
```

Remover `md:ml-20`. Garantir que TopBar.vue tenha o botao hamburguer em viewport tablet.

- [ ] **Step 2.3: Atualizar TopBar para refletir decisao**

`resources/js/Components/TopBar.vue`: ajustar logica de exibicao do hamburguer (`v-if="isMobile || isTablet"`).

- [ ] **Step 2.4: Verificar SidebarOnlyLayout**

Aplicar mesma correcao em `resources/js/Layouts/SidebarOnlyLayout.vue` para manter consistencia.

- [ ] **Step 2.5: Teste Playwright especifico**

Criar `tests/e2e/responsividade-tablet.spec.js`:

```js
test('dashboard sem gap em viewport tablet 895px', async ({ page }) => {
  await page.setViewportSize({ width: 895, height: 900 });
  await page.goto('/dashboard');
  const main = page.locator('main');
  const bbox = await main.boundingBox();
  expect(bbox.x).toBeLessThan(85); // <80px de margem
});
```

- [ ] **Step 2.6: Commit**

```bash
git add resources/js/Layouts/ resources/js/Components/Sidebar.vue tests/e2e/responsividade-tablet.spec.js
git commit -m "fix(layout): elimina gap vazio da sidebar em viewport tablet (768-1023px)"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 3: Consolidacao de Breakpoints e Design Tokens

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
**Files:**
- Create: `resources/js/Composables/useResponsive.js` (wrapper expandido de useMobile)
- Create: `resources/sass/tokens.scss` ou `tailwind.config.js` (extend.spacing)
- Modify: 21 arquivos com `@media` inline (ver Step 1.1)

- [ ] **Step 3.1: Centralizar breakpoints**

Tornar `BREAKPOINTS` de `useMobile.js` exportavel e referenciado em CSS via custom properties (`:root { --bp-md: 768px; }`).

- [ ] **Step 3.2: Substituir `@media` inline por classes Tailwind**

Para cada um dos 21 arquivos detectados em Step 1.1, converter `@media (max-width: 768px)` em utility classes Tailwind (`md:hidden`, `md:flex`, etc) sempre que viavel. Manter `@media` so para casos complexos (orientacao, hover, print).

Priorizar (alta densidade): `BottomNavigation.vue`, `Sidebar.vue`, `TopBar.vue`, `Dropdown.vue`, `FormField.vue`.

- [ ] **Step 3.3: Definir tokens de touch target**

Tailwind `theme.extend.spacing`:
```js
spacing: {
  'touch-min': '44px',  // iOS HIG minimum
  'touch-rec': '48px',  // Material recommended
}
```

Auditar todos os `<button>` e `<a>` em mobile: garantir `min-h-touch-min`.

- [ ] **Step 3.4: Adicionar regras de overflow para tabelas**

Mapear `ResponsiveTable.vue` e `TableMobileCard.vue`. Garantir `overflow-x-auto` + scroll snap em tabelas largas no breakpoint `md`.

- [ ] **Step 3.5: Commit**

```bash
git add resources/ tailwind.config.js
git commit -m "refactor(responsive): consolida breakpoints e design tokens"
```

---

## Fase 4: Bug Sweep por Resolucao

**Files:**
- Create: `tests/e2e/viewports.spec.js`
- Read/Modify: paginas das 4 screenshots + paginas com `v-html` (11 arquivos)

**Matriz de viewports a testar:**

| Nome | Largura | Altura | Tipo |
|------|---------|--------|------|
| mobile-s | 320 | 568 | iPhone SE |
| mobile-m | 375 | 812 | iPhone 12 |
| mobile-l | 414 | 896 | iPhone Pro Max |
| tablet-p | 768 | 1024 | iPad portrait |
| tablet-l | 1024 | 768 | iPad landscape |
| laptop | 1366 | 768 | Laptop comum |
| desktop | 1920 | 1080 | Full HD |
| ultrawide | 2560 | 1440 | QHD |

- [ ] **Step 4.1: Smoke test multi-viewport**

Criar suite Playwright parametrizada que para cada (pagina, viewport):
1. Carrega a pagina
2. Verifica `console.error` count = 0
3. Snapshot visual
4. Verifica que nao ha scroll horizontal (`document.documentElement.scrollWidth <= window.innerWidth`)

Paginas: `/dashboard`, `/rat`, `/pae`, `/decretacoes`, `/demandas`, `/admin/permissions/users`, `/tdap/processos`, `/inventario`.

- [ ] **Step 4.2: Investigar overflow-x detectados**

Para cada falha de overflow horizontal, identificar elemento culpado e corrigir com `min-w-0`, `truncate` ou `overflow-x-auto`.

- [ ] **Step 4.3: Auditar Pages com cards numericos**

Dashboard: cards "RAT Abertas", "PAE Em Analise" usam grid. Verificar se em 768-900px viram 1 coluna ou ficam apertados. Aplicar `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4` consistente.

- [ ] **Step 4.4: ApexCharts responsivo**

`apexcharts` (Dashboard, Pae) precisa de listener de resize. Verificar `chart.options.chart.responsive` e adicionar config:
```js
responsive: [{
  breakpoint: 768,
  options: { chart: { height: 250 }, legend: { position: 'bottom' } }
}]
```

- [ ] **Step 4.5: Validar Modal/Drawer em mobile**

`Modal.vue` deve ocupar 100vw em mobile e centralizar em desktop. Verificar `max-w-*` e `inset-x-0` em mobile.

- [ ] **Step 4.6: Commit**

```bash
git add tests/e2e/viewports.spec.js resources/
git commit -m "fix(responsive): corrige overflow horizontal e ajustes por viewport"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 5: Refactor Leve da Sidebar (1464 LOC)

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
**Files:**
- Modify: `resources/js/Components/Sidebar.vue`
- Create: `resources/js/Components/Organisms/Navigation/SidebarSection.vue`
- Create: `resources/js/Components/Organisms/Navigation/SidebarSubmenu.vue`
- Create: `resources/js/Composables/useSidebarNavigation.js`

**Justificativa:** Componente unico com 1464 linhas mistura template, logica de permissoes, scroll-spy, e submenu. Atende rule #4 (SOLID/DRY).

- [ ] **Step 5.1: Extrair logica de permissoes para composable**

Mover `canSeeRat`, `canSeePae`, `canSeePlantao`, etc para `useSidebarNavigation.js`. Centralizar checks de `_routes.has*`.

- [ ] **Step 5.2: Extrair `nav-section`s como componente**

`SidebarSection.vue` recebe `title` e default slot. Reduz template repetitivo (PRINCIPAL, MODULOS DE GESTAO, ADMINISTRACAO, INTEGRACOES).

- [ ] **Step 5.3: Extrair submenu como componente**

`SidebarSubmenu.vue` encapsula transicao, scroll, e fechamento. Recebe `items` como prop.

- [ ] **Step 5.4: Validar zero regressoes**

Rodar suite Playwright completa antes de aceitar. Diff visual deve ser zero.

- [ ] **Step 5.5: Commit**

```bash
git add resources/js/Components/ resources/js/Composables/
git commit -m "refactor(sidebar): extrai SidebarSection, SidebarSubmenu e useSidebarNavigation"
```

---

## Fase 6: Auditoria de Seguranca OWASP

**Files:**
- Read: `app/Http/Controllers/**/*.php`
- Read: `app/Modules/**/Controllers/*.php`
- Read: `app/Http/Middleware/SanitizeInput.php`, `SecurityHeaders.php`, `VerifyCsrfToken.php`
- Read: 11 arquivos Vue com `v-html`
- Create: `docs/superpowers/audits/2026-05-15-security-report.md`

**Resultado preliminar:**
- 24 ocorrencias de `DB::raw|whereRaw|orderByRaw|selectRaw` em 10 arquivos -> revisar
- 11 arquivos com `v-html` -> revisar XSS
- 1 ocorrencia de `eval|exec|system` no app (`HealthCheckController.php`) -> verificar contexto

### A. Injection (SQL)

- [ ] **Step 6.A.1: Revisar `whereRaw/DB::raw`**

Para cada um dos 10 arquivos, verificar se input do usuario chega via concatenacao (`whereRaw("name = '$nome'")`) ou via bindings (`whereRaw("name = ?", [$nome])`). Listar e corrigir.

Comando:
```bash
rg "DB::raw|whereRaw|orderByRaw|selectRaw" app -n -A2
```

Arquivos prioritarios identificados:
- `app/Modules/Decretacoes/Services/ProcessoQueryService.php` (9 ocorrencias)
- `app/Modules/AjudaHumanitaria/Services/BeneficiarioService.php` (2)
- `app/Modules/AjudaHumanitaria/Models/Abrigo.php` (4)

- [ ] **Step 6.A.2: Adicionar testes de regressao**

Criar `tests/Feature/Security/SqlInjectionTest.php` que passa payloads `'; DROP TABLE--`, `1 OR 1=1`, `' UNION SELECT ...` nos endpoints de busca/filtro mais expostos e verifica codigo de resposta != 500 e ausencia de dados sensiveis.

### B. XSS

- [ ] **Step 6.B.1: Revisar 11 arquivos com `v-html`**

Para cada uso, verificar se o conteudo vem de:
- Constante hardcoded (OK)
- API com sanitizacao server-side (OK)
- Input do usuario sem sanitizacao (CRITICO)

Arquivos:
- `Pages/Tdap/Viagens/Pendentes.vue`, `Processos/Index.vue`, `Vistorias/Index.vue`, `Cronogramas/Index.vue`, `Lotes/Index.vue`, `Atas/Index.vue`, `Historicos/Index.vue`, `Caminhoes/Index.vue`, `Prestadores/Index.vue`
- `Pages/Admin/Permissions/Users/Index.vue`
- `Components/Molecules/PlanCon/PlanConLinkCard.vue`

- [ ] **Step 6.B.2: Substituir `v-html` por componente sanitizado**

Onde necessario, usar DOMPurify (`npm i dompurify`) e criar `<SafeHtml>` que limpa antes de renderizar.

- [ ] **Step 6.B.3: Validar CSP headers**

`SecurityHeaders.php`: garantir `Content-Security-Policy` com `default-src 'self'`, `script-src 'self'` (sem `unsafe-inline` em prod).

### C. CSRF

- [ ] **Step 6.C.1: Verificar VerifyCsrfToken**

Listar rotas em `$except` de `VerifyCsrfToken.php`. Cada uma precisa de justificativa documentada (webhook externo, etc).

- [ ] **Step 6.C.2: Verificar Sanctum/Inertia**

Inertia ja injeta CSRF automaticamente. Verificar que requisicoes axios manuais (`bootstrap.js`) tambem usam o token.

### D. IDOR (Insecure Direct Object Reference)

- [ ] **Step 6.D.1: Auditar Controllers que recebem `$id`**

```bash
rg "function (show|edit|update|destroy)\(.*\\\$id" app/Modules -n
```

Para cada, verificar se ha:
- Policy/Gate check (`$this->authorize('view', $model)`)
- Filtro por tenant (`->where('tenant_id', auth()->user()->tenant_id)`)
- Filtro por owner

Listar todos sem autorizacao em `security-report.md`.

- [ ] **Step 6.D.2: Adicionar testes de IDOR**

`tests/Feature/Security/AuthorizationTest.php`: usuario A nao consegue ler/editar recursos do usuario B.

### E. Outros (Top 10)

- [ ] **Step 6.E.1: Mass assignment**

Grep por Models sem `$fillable` ou com `$guarded = []`. Listar.

- [ ] **Step 6.E.2: Open redirect**

Grep por `redirect($request->input(...))` ou `Redirect::to($url)` onde `$url` vem do request.

- [ ] **Step 6.E.3: SSRF**

Grep por `Http::get($url)` / `file_get_contents($url)` / `curl` com URL vindo do request. Validar whitelist de hosts.

- [ ] **Step 6.E.4: Insecure deserialization**

Grep por `unserialize(`. Se houver, verificar origem do dado.

- [ ] **Step 6.E.5: Storage publico vazado**

```bash
ls public/storage/ public/uploads/ 2>/dev/null
```

Verificar se ha arquivos sensiveis acessiveis publicamente.

- [ ] **Step 6.E.6: HealthCheckController**

Validar que o uso de `eval|exec` em `app/Http/Controllers/Api/HealthCheckController.php` e legitimo (provavelmente comando shell para `disk_free_space`) e que a rota tem middleware `auth:sanctum` ou esta restrita a IPs internos.

### F. Relatorio e Commit

- [ ] **Step 6.F.1: Consolidar `security-report.md`**

Para cada finding: severidade (CRITICO/ALTO/MEDIO/BAIXO), arquivo:linha, descricao, recomendacao, status (corrigido/pendente).

- [ ] **Step 6.F.2: Commit**

```bash
git add app/ resources/js/ tests/Feature/Security/ docs/superpowers/audits/
git commit -m "security: corrige findings OWASP Top 10 (SQLi, XSS, IDOR, mass-assignment)"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 7: Auditoria de Dependencias

<<<<<<< Updated upstream
<<<<<<< Updated upstream
- [ ] **7.1**: `composer audit` + `composer outdated --direct`
- [ ] **7.2**: `npm audit` + `npm outdated`
- [ ] **7.3**: Verificar versoes pilares (Laravel 12, Vue 3.5+, Inertia 2.2+, Vite 5, Tailwind 3)
- [ ] **7.4**: Pacotes abandonados (`composer show | grep abandon`)
- [ ] **7.5**: Confirmar lockfiles versionados
- [ ] **7.6**: Commit
=======
=======
>>>>>>> Stashed changes
**Files:**
- Read: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`
- Create: `docs/superpowers/audits/2026-05-15-deps-report.md`

- [ ] **Step 7.1: Audit composer**

```bash
composer audit --format=json > docs/superpowers/audits/composer-audit.json
composer outdated --direct --format=json > docs/superpowers/audits/composer-outdated.json
```

Classificar advisories por severidade. Atualizar pacotes com fix disponivel.

- [ ] **Step 7.2: Audit npm**

```bash
npm audit --json > docs/superpowers/audits/npm-audit.json
npm outdated --json > docs/superpowers/audits/npm-outdated.json
```

Resolver criticos via `npm audit fix`. Para vulnerabilidades em devDeps, avaliar custo/beneficio.

- [ ] **Step 7.3: Verificar versoes dos pilares**

Confirmar que estao em versao com suporte de seguranca:
- Laravel 12.x (LTS check)
- Vue 3.4+ (3.5+ recomendado)
- Inertia 2.2+
- Vite 5.x
- Tailwind 3.x (avaliar custo de migrar para 4)

- [ ] **Step 7.4: Verificar ausencia de pacotes abandonados**

```bash
composer show --direct | grep -i abandon
```

- [ ] **Step 7.5: Lockfiles fora do .gitignore**

Confirmar que `composer.lock` e `package-lock.json` estao commitados (deploy reprodutivel).

- [ ] **Step 7.6: Commit**

```bash
git add composer.json composer.lock package.json package-lock.json docs/superpowers/audits/
git commit -m "chore(deps): atualiza pacotes com CVE e remove abandonados"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 8: UX e Responsividade por Modulo (Web + Mobile)

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
**Goal:** Cada modulo recebe revisao dedicada nos 3 modos de uso: **Desktop (>=1280px)**, **Tablet/Laptop (768-1279px)** e **Mobile (<768px)**. Foco em densidade de informacao apropriada para cada viewport, touch targets, gestos e fluxos otimizados.

**Estrutura por modulo:**
Cada subsecao (8.1 a 8.12) cobre **Web (desktop/tablet)** e **Mobile** separadamente, com tarefas especificas para cada.

**Padroes WEB compartilhados (desktop/tablet, >=768px):**
- Layout grid de no maximo 12 colunas com gutter consistente (`gap-4` em sm, `gap-6` em lg)
- Tabelas com colunas sticky (1a coluna = nome/codigo, ultima = acoes) e scroll horizontal interno apenas quando necessario
- KPI cards: `grid-cols-2 md:grid-cols-3 lg:grid-cols-4` evitando span solto
- Filtros: sidebar lateral colapsavel em desktop (>=1280px), accordion em tablet
- Modais centrados com `max-w-3xl` para forms longos
- Densidade alta: aproveitar real estate em monitores ultrawide com 2-column layouts onde aplicavel

**Padroes MOBILE compartilhados (<768px):**
- Touch target minimo: 44x44px (iOS HIG)
- Cards KPI: `grid-cols-2` com auto-distribute (evita 5 cards apertados)
- Tabelas: converter para `<TableMobileCard>` (mostrar so 2-3 colunas essenciais + drawer de detalhes)
- Acoes destrutivas: confirmacao via `<ActionSheet>` (bottom drawer), nunca confirm() nativo
- Filtros pesados: `<FilterBottomSheet>` invocado por chip "Filtros"
- Header sticky com botao voltar + acoes principais
- Footer/bottom-nav fora de modulos que ja tem bottom nav

**Files compartilhados:**
- Create: `resources/js/Components/Molecules/Mobile/MobilePageHeader.vue`
- Create: `resources/js/Components/Molecules/Mobile/MobileKpiGrid.vue`
- Create: `resources/js/Components/Molecules/Mobile/FilterBottomSheet.vue`
- Create: `resources/js/Components/Molecules/Mobile/ActionSheet.vue`
- Modify: `resources/js/Components/Organisms/Table/ResponsiveTable.vue`

### 8.0: Componentes base compartilhados (Web + Mobile)

- [ ] **Step 8.0.1: KpiGrid responsivo** (Web + Mobile)

Grid que evita "5 cards em 2 colunas" (impar fica solitario). Auto-detecta numero de cards e ajusta:
- Mobile (<640): `grid-cols-1` ou `grid-cols-2` (se par)
- Tablet (640-1023): `grid-cols-2` ou `grid-cols-3`
- Desktop (>=1024): `grid-cols-3`, `grid-cols-4` ou `grid-cols-5` conforme quantidade

- [ ] **Step 8.0.2: FilterPanel (Web) + FilterBottomSheet (Mobile)**

Mesmo componente API, dois renders:
- Web: sidebar lateral em desktop (`<DesktopFilterSidebar>`) com toggle para colapsar, accordion em tablet
- Mobile: drawer slide-up (`<FilterBottomSheet>`) com botoes "Aplicar"/"Limpar"

Usar `@vueuse/core useScrollLock` em mobile para travar scroll do body quando aberto.

- [ ] **Step 8.0.3: ResponsiveTable refactor**

Auto-switch baseado em viewport:
- Desktop: tabela completa com todas as colunas, scroll horizontal interno se necessario, sticky col1+colN
- Tablet: tabela com colunas prioritarias (`priorityColumns`) + botao "Ver mais detalhes"
- Mobile: `TableMobileCard` com 2-3 dados-chave por card + tap abre drawer com detalhes

- [ ] **Step 8.0.4: Skeleton sem race condition**

Fix do bug visivel: `isPageLoading` fica `true` indefinidamente em algumas paginas (Compdec mobile screenshot). Auditar `usePageLoading.js` para garantir reset em `router.on('finish')` e timeout maximo de 10s.

- [ ] **Step 8.0.5: PageHeader unificado (Web + Mobile)**

Componente que renderiza:
- Web (>=768): titulo grande + descricao + botoes de acao a direita (Exportar, Novo, etc)
- Mobile (<768): titulo medio + chip de acoes (3-dot menu se >2 acoes), botao primario fixo

- [ ] **Step 8.0.6: Commit base**

```bash
git add resources/js/Components/ resources/js/Composables/usePageLoading.js
git commit -m "feat(ux): componentes base responsivos (KpiGrid, FilterPanel, ResponsiveTable, PageHeader)"
```

### 8.1: RAT (Relatorio de Atendimento Tactico)

**Files:** `resources/js/Pages/Rat.vue`, `resources/js/Pages/RatIndex.vue`, `resources/js/Components/Rat/RatTabs.vue`, `resources/js/Components/Molecules/Rat/RatCard.vue`

- [ ] **Step 8.1.1**: Lista de RATs em mobile usa `RatCard` (ja existe). Garantir tap-area >= 44px e badge de status visivel.
- [ ] **Step 8.1.2**: Form de criacao em mobile - converter `RatTabs` em wizard step-by-step (`<MobileWizard>`) em vez de abas horizontais.
- [ ] **Step 8.1.3**: Upload de foto via camera nativa (`<input type="file" accept="image/*" capture="environment">`).
- [ ] **Step 8.1.4**: Geolocalizacao para "local do fato" via `navigator.geolocation`.
- [ ] **Step 8.1.5**: Modo offline - salvar rascunho via Dexie/IndexedDB (ja instalado), sincronizar quando online.

### 8.2: PAE (Painel de Acoes Emergenciais)

**Files:** `resources/js/Pages/Pae.vue`, `resources/js/Pages/PaeProtocolosIndex.vue`, `resources/js/Components/Pae/`

- [ ] **Step 8.2.1**: Painel Gerencial em mobile - KPI cards em 2 colunas (`grid-cols-2 md:grid-cols-4`), nao 1.
- [ ] **Step 8.2.2**: Lista de protocolos em mobile usa card view (substituir tabela atual com `#13.05.2026.016, N/A, Nao atribuido...`).
- [ ] **Step 8.2.3**: Filtros pesados (`CCPAE`, `Grade/Tabela`, periodos) em `FilterBottomSheet`.
- [ ] **Step 8.2.4**: Acoes (`ver, imprimir, editar, vencimento, deletar`) em `ActionSheet` apos tap longo no card.
- [ ] **Step 8.2.5**: Tab `Total/Historico/Vencidos/CCPAE` como `<ChipFilter>` horizontal scrollavel.

### 8.3: Decretacoes

**Files:** `resources/js/Pages/Decretacoes/Index.vue`, `resources/js/Components/Organisms/Decretacoes/`

- [ ] **Step 8.3.1**: 5 cards KPI distribuir em `grid-cols-2` com ultimo card span-2, ou usar carousel horizontal.
- [ ] **Step 8.3.2**: Filtros Avancados + Filtro COBRADE em accordion mobile.
- [ ] **Step 8.3.3**: Lista de processos como `RatCard`-style com chip de tipo (ECP/SE).
- [ ] **Step 8.3.4**: Mapa de municipios atingidos com layer responsivo (`L.map` com `tap: true` para mobile).

### 8.4: Compdec / Orgaos

**Files:** `resources/js/Pages/Compdec/OrgaoShow.vue`, `resources/js/Pages/Compdec/Index.vue`

- [ ] **Step 8.4.1**: Fix do overflow horizontal visivel nas screenshots - cabecalho da tabela `CODIGO/NOME/TIPO/MUNICIPIO/STATUS/USUARIOS/ACOES` precisa virar cards com so `NOME + chip TIPO + badge STATUS + icone ACAO` em mobile.
- [ ] **Step 8.4.2**: KPIs `Total/COMPDECs/REDECs/Ativos` em 2x2 grid.
- [ ] **Step 8.4.3**: OrgaoShow com tabs `Geral/Capacidades/Prefeitura` em `<TabsMobile>` (scrollavel horizontal).

### 8.5: Demandas

**Files:** `resources/js/Pages/Demandas/`, `resources/js/Pages/DemandasIndex.vue`

- [ ] **Step 8.5.1**: Cards de demanda com indicador de SLA (cor por urgencia).
- [ ] **Step 8.5.2**: Swipe-to-action: swipe direita = atender, swipe esquerda = arquivar.
- [ ] **Step 8.5.3**: Pull-to-refresh ja implementado (`PullToRefresh.vue`) - verificar se esta ativo aqui.

### 8.6: TDAP (Termo de Doacao/Atas/Prestadores)

**Files:** `resources/js/Pages/Tdap/**`

- [ ] **Step 8.6.1**: Como TDAP tem sub-modulos (Viagens, Processos, Vistorias, Cronogramas, Lotes, Atas, Historicos, Caminhoes, Prestadores), criar `<TdapHub>` em mobile como grid de 9 cards-icone.
- [ ] **Step 8.6.2**: Pendentes (viagens) - cards com botao "Aprovar" / "Reprovar" inline.
- [ ] **Step 8.6.3**: Vistoria em mobile com upload de fotos sequencial + geotagging.

### 8.7: Ajuda Humanitaria

**Files:** `resources/js/Pages/AjudaHumanitaria/`

- [ ] **Step 8.7.1**: Lista de beneficiarios com avatar + nome + chip de status.
- [ ] **Step 8.7.2**: Cadastro de familia em wizard mobile (`<MobileWizard>`).
- [ ] **Step 8.7.3**: Estoque de itens com search bar sticky no topo + scan de codigo via camera.

### 8.8: Inventario

**Files:** `resources/js/Pages/Inventario/`

- [ ] **Step 8.8.1**: Lista de itens com thumbnail + nome + qtd.
- [ ] **Step 8.8.2**: Cadastro com scan de QR/barcode (`@zxing/library` se for incluido).

### 8.9: Plantao Diario

**Files:** `resources/js/Pages/Plantao/`

- [ ] **Step 8.9.1**: Timeline vertical de eventos (mais natural em mobile que tabela).
- [ ] **Step 8.9.2**: Botao flutuante "+ Novo Evento" (`<FAB>`) fixo no canto inferior direito.

### 8.10: Inmet / Meteorologia

**Files:** `resources/js/Pages/Inmet/MapaInmet.vue`

- [ ] **Step 8.10.1**: Mapa em fullscreen mobile (sem padding) com card de legenda colapsavel.
- [ ] **Step 8.10.2**: Touch gestures (pinch zoom, pan) ja inclusos em Leaflet - garantir `tap: true`.

### 8.11: PlanCon / Cisternas / Treinamento

- [ ] **Step 8.11.1**: PlanConLinkCard - revisar uso de `v-html` (XSS - tratado em Fase 6) e tap area.
- [ ] **Step 8.11.2**: Cisternas - lista geolocalizada com mapa colapsavel no topo.
- [ ] **Step 8.11.3**: Treinamento - cards de cursos com badge de progresso.

### 8.12: BottomNavigation

**Files:** `resources/js/Components/Molecules/Navigation/BottomNavigation.vue`

- [ ] **Step 8.12.1**: Auditar tabs ativas. Recomendado: `Visao Geral / DEMANDAS / RAT / PAE / Mais...`
- [ ] **Step 8.12.2**: Indicador visual de modulo ativo + badge de notificacoes.
- [ ] **Step 8.12.3**: "Mais..." abre `ActionSheet` com os demais modulos.

### 8.13: Commit final mobile

- [ ] **Step 8.13.1**: Snapshot Playwright em viewport 375px e 414px para cada modulo.
- [ ] **Step 8.13.2**: Lighthouse Mobile (`--preset=mobile`) >= 85.

```bash
git add resources/js/Pages/ resources/js/Components/
git commit -m "feat(mobile): UX dedicada por modulo (RAT, PAE, Compdec, Decretacoes, TDAP, etc)"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Fase 9: NativePHP (App Nativo)

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
**Goal:** Empacotar NewSDC como app nativo para desktop e mobile, aproveitando o stack Laravel ja existente.

**NativePHP fornece:**
- `nativephp/electron`: app desktop (Win/Mac/Linux) com PHP embedado
- `nativephp/ios`: app iOS (build via Xcode)
- `nativephp/android`: app Android (build via Android Studio)

**Pre-requisitos:** o NewSDC ja tem `@vite-pwa/plugin` e service worker - boa base para evoluir.

### 9.A: Avaliacao e POC

- [ ] **Step 9.A.1: Decisao arquitetural**

Tres caminhos possiveis - escolher um:

| Caminho | Pros | Contras |
|---------|------|---------|
| **A. PWA pura** (ja existe) | Zero esforco extra, funciona em qualquer device | Sem acesso a APIs nativas (geofencing background, etc) |
| **B. NativePHP (Electron + iOS + Android)** | Stack PHP unica, build com `php artisan native:run` | Beta maturidade, bundle maior, gestao de release stores |
| **C. Capacitor/Tauri (wrap PWA)** | Maduro, plugins nativos ricos | Stack extra (Node + Rust), nao e "PHP-native" |

Recomendacao inicial: **Caminho B (NativePHP)** alinhado a stack Laravel.

- [ ] **Step 9.A.2: Instalar NativePHP**

```bash
composer require nativephp/electron
php artisan native:install
```

- [ ] **Step 9.A.3: POC Desktop**

Criar `app/Providers/NativeAppServiceProvider.php` com janela inicial apontando para o painel principal. Validar:
- Login funciona
- Inertia carrega
- Vite serve assets corretamente
- SQLite local funciona como cache offline

- [ ] **Step 9.A.4: Decidir backend offline**

Em modo nativo, o backend Laravel roda embarcado (PHP via FrankenPHP). Decidir:
- SQLite local como replica (read-only) com sync periodica do MySQL remoto
- Ou modo "thin client" - app nativo so consome API remota, sem backend local

Para uso de campo da Defesa Civil em areas sem sinal, opcao SQLite local + sync e superior.

### 9.B: iOS

- [ ] **Step 9.B.1: Instalar nativephp/ios**

```bash
composer require nativephp/ios
php artisan native:ios
```

- [ ] **Step 9.B.2: Adaptar UI**

- Safe areas (notch/dynamic island): ja parcialmente tratado em `AuthenticatedLayout.vue:77` com `env(safe-area-inset-top)`. Auditar todos os layouts.
- Status bar: configurar `UIStatusBarStyle` para tema escuro
- App icon e splash screen com identidade visual da Defesa Civil MG

- [ ] **Step 9.B.3: Permissoes**

`Info.plist`:
- `NSCameraUsageDescription`: "Para capturar fotos de ocorrencias"
- `NSLocationWhenInUseUsageDescription`: "Para registrar localizacao de eventos"
- `NSPhotoLibraryUsageDescription`: "Para anexar fotos a relatorios"

- [ ] **Step 9.B.4: Build TestFlight**

Documentar processo de build para TestFlight (interno). Criar `docs/superpowers/nativephp/ios-build.md`.

### 9.C: Android

- [ ] **Step 9.C.1: Instalar nativephp/android**

```bash
composer require nativephp/android
php artisan native:android
```

- [ ] **Step 9.C.2: Permissoes Manifest**

```xml
<uses-permission android:name="android.permission.CAMERA"/>
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE"/>
```

- [ ] **Step 9.C.3: Adaptive icon, splash screen, tema material**

- [ ] **Step 9.C.4: Build APK**

Gerar APK assinado para distribuicao interna. Avaliar Play Store mais tarde.

### 9.D: APIs nativas via Bridge

NativePHP expoe APIs PHP que mapeiam para nativo:

- [ ] **Step 9.D.1: Notificacoes**

```php
use Native\Laravel\Facades\Notification;
Notification::title('Nova RAT urgente')->message('...')->show();
```

- [ ] **Step 9.D.2: GPS background**

Para Plantao Diario / RAT em campo - rastrear posicao do agente sem app aberto.

- [ ] **Step 9.D.3: Camera nativa**

Substituir `<input type="file" capture>` por API nativa mais robusta.

- [ ] **Step 9.D.4: Filesystem**

Salvar PDFs/relatorios localmente (`Native\Laravel\Facades\FileSystem`).

- [ ] **Step 9.D.5: Sync offline**

Worker em background que sincroniza dados quando recupera conexao.

### 9.E: Distribuicao

- [ ] **Step 9.E.1: Documentar pipeline de build**

`docs/superpowers/nativephp/build-pipeline.md`:
- Como gerar `.exe` Windows
- Como gerar `.dmg` macOS
- Como gerar `.AppImage` Linux
- Como gerar `.ipa` iOS (Apple Developer Account requerido)
- Como gerar `.apk` Android

- [ ] **Step 9.E.2: Sistema de auto-update**

Configurar auto-updater do Electron + EAS Update para mobile.

- [ ] **Step 9.E.3: Commit final**

```bash
git add nativephp.config.php app/Providers/NativeAppServiceProvider.php docs/superpowers/nativephp/
git commit -m "feat(native): empacota NewSDC como app nativo (electron + ios + android)"
```
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Entregaveis Finais

| Artefato | Caminho |
|----------|---------|
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
| Relatorio de auditoria visual | `docs/superpowers/audits/2026-05-15-baseline.md` |
| Relatorio de seguranca | `docs/superpowers/audits/2026-05-15-security-report.md` |
| Relatorio de dependencias | `docs/superpowers/audits/2026-05-15-deps-report.md` |
| Suite E2E multi-viewport | `tests/e2e/viewports.spec.js` |
| Testes de seguranca | `tests/Feature/Security/SqlInjectionTest.php`, `AuthorizationTest.php` |
| Fix do gap tablet | commits em Fase 2 |
| Sidebar refatorada | commits em Fase 5 |
| Componentes mobile base | `resources/js/Components/Molecules/Mobile/{MobilePageHeader,MobileKpiGrid,FilterBottomSheet,ActionSheet}.vue` |
| Apps nativos | builds Electron (.exe/.dmg/.AppImage) + iOS (.ipa) + Android (.apk) |
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
| Docs NativePHP | `docs/superpowers/nativephp/{ios-build,android-build,build-pipeline}.md` |

---

## Criterios de Aceitacao

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
- [ ] Screenshot do dashboard em 895px nao mostra faixa preta vazia a esquerda
- [ ] Suite Playwright passa nos 8 viewports definidos
- [ ] Zero overflow horizontal em qualquer viewport >= 320px
- [ ] `composer audit` retorna `0 advisories` ou advisories aceitos com justificativa documentada
- [ ] `npm audit --audit-level=high` retorna `0 vulnerabilities`
- [ ] Todos os usos de `v-html` mapeados como seguros ou substituidos por `<SafeHtml>`
- [ ] Todos os `whereRaw` usam bindings (zero concatenacao com input)
- [ ] Sidebar.vue reduzida para <800 linhas
- [ ] Lighthouse Performance >= 80 em todas as paginas em viewport desktop
- [ ] **Lighthouse Mobile (`--preset=mobile`) >= 85 em todas as paginas**
- [ ] **Skeleton loader nao fica travado em nenhuma pagina apos navegacao**
- [ ] **Tabelas em mobile usam `TableMobileCard` automaticamente (zero overflow horizontal em viewport 320px)**
- [ ] **KPI grids nunca terminam com card solitario na ultima linha**
- [ ] **Todos os touch targets >= 44x44px em mobile**
- [ ] **Build NativePHP (Electron) executa sem erros e abre janela funcional**
- [ ] **APK Android assinado gerado com sucesso para distribuicao interna**
- [ ] **App iOS roda em simulador Xcode (build TestFlight opcional, requer Apple Dev account)**
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Riscos e Mitigacoes

| Risco | Probabilidade | Mitigacao |
|-------|---------------|-----------|
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
| Refactor da Sidebar quebra navegacao | Media | Snapshots Playwright + diff visual obrigatorio antes de merge |
| `composer update` quebra Laravel/Octane | Media | Atualizar incrementalmente, rodar `php artisan test` apos cada |
| Remocao de `md:ml-20` quebra desktop | Baixa | Teste Playwright em viewport 1280, 1440, 1920 antes de commit |
| DOMPurify aumenta bundle | Baixa | Carregar como `defineAsyncComponent` no `<SafeHtml>` |
| Tempo total >85h | Alta | Sprints separados; Fase 9 pode ser projeto a parte |
| NativePHP ainda em maturidade beta | Alta | Validar POC em Fase 9.A antes de comprometer iOS/Android. Fallback: PWA pura ja existente |
| Build iOS exige macOS + Apple Developer ($99/ano) | Media | Priorizar Android + Electron primeiro; iOS so apos validacao com stakeholders |
| Sync offline pode causar conflitos de dados | Media | Definir estrategia "last-write-wins" ou CRDT simples para campos editaveis em campo |
| Refactor de tabelas para card view quebra fluxos de bulk-action | Media | Manter modo `view=table` em mobile como opcao avancada via toggle |
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

---

## Ordem de Execucao Recomendada

<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
=======
>>>>>>> Stashed changes
1. **Fase 0** (6h) - Fundacao: contrato unico de responsividade + catalogo de divergencias entre modulos
2. **Fase 1** (4h) - Baseline obrigatorio
3. **Fase 2** (3h) - Fix critico visivel ao usuario (gap tablet)
4. **Fase 8.0** (4h) - Componentes base compartilhados (skeleton fix, FilterPanel/BottomSheet, ActionSheet, KpiGrid, PageHeader)
4. **Fase 6** (12h) - Seguranca antes de seguir
5. **Fase 7** (2h) - Audit deps em paralelo com Fase 6
6. **Fase 4** (6h) - Bug sweep multi-viewport
7. **Fase 8.1-8.12** (16h) - Mobile UX por modulo (paralelizavel por modulo)
8. **Fase 3** (6h) - Consolidacao breakpoints (recomendado)
9. **Fase 5** (8h) - Refactor Sidebar (recomendado)
10. **Fase 9.A** (4h) - POC NativePHP Electron desktop
11. **Fase 9.B** (8h) - iOS (requer macOS + Apple Dev)
12. **Fase 9.C** (6h) - Android (Windows OK)
13. **Fase 9.D-E** (6h) - APIs nativas + distribuicao

**Total estimado:** 91h
- Obrigatorias (1, 2, 4, 6, 7, 8.0): 31h
- Mobile por modulo (8.1-8.12): 16h
- Refactors recomendados (3, 5): 14h
- NativePHP (9): 24h

**Estrategia recomendada por sprints:**
- **Sprint 1 (1 semana)**: Fases 1, 2, 8.0, 6, 7 - estabilidade e seguranca
- **Sprint 2 (1 semana)**: Fases 4, 8.1-8.6 - mobile dos modulos criticos (RAT, PAE, Compdec, Decretacoes, Demandas, TDAP)
- **Sprint 3 (1 semana)**: Fases 8.7-8.12, 3, 5 - mobile restante + refactor
- **Sprint 4 (1-2 semanas)**: Fase 9 - NativePHP (paralelizavel: Android + Electron em Windows, iOS em macOS)
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
