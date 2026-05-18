# Contrato Unico de Responsividade - NewSDC

> **Status:** OBRIGATORIO desde 2026-05-18 (Fase 0 da auditoria frontend).
> Toda nova feature, refactor ou correcao deve seguir este contrato.
> O lint custom `scripts/eslint-rules/no-bare-table.js` falha o build em violacoes basicas.

## 1. Breakpoints (fonte unica de verdade)

Todos os breakpoints derivam de `resources/js/Composables/mobile/useMobile.js`:

| Nome | Faixa | Tailwind prefix | Uso tipico |
|------|-------|-----------------|------------|
| Mobile | `< 768px` | (default) | Smartphones, foco em uma coluna, drawer overlay, touch primary |
| Tablet | `768px - 1023px` | `md:` | iPad portrait, laptops compactos, sidebar collapsed (rail), 2 colunas |
| Desktop | `>= 1024px` | `lg:` | Monitores, sidebar expandida 280px, 3-4 colunas |
| Wide | `>= 1280px` | `xl:` | Real estate extra para sidebars de filtros, painel direito |
| Ultrawide | `>= 1536px` | `2xl:` | Densidade maxima, multi-panel layouts |

**Regras:**
- NUNCA usar `@media` inline em `<style>` quando Tailwind utility resolve.
- NUNCA criar breakpoint customizado (`@media (min-width: 850px)`) sem documentar aqui.
- Usar `useMobile()` em logica JS, nao `window.innerWidth` direto.

## 2. KPI Cards

Componente obrigatorio: `<KpiGrid>` (Components/Molecules/Mobile/KpiGrid.vue).

| Qtd cards | Mobile (<640) | Tablet (640-1023) | Desktop (>=1024) |
|-----------|---------------|---------------------|-------------------|
| 2 | `grid-cols-2` | `grid-cols-2` | `grid-cols-2` |
| 3 | `grid-cols-1` | `grid-cols-3` | `grid-cols-3` |
| 4 | `grid-cols-2` | `grid-cols-2` | `grid-cols-4` |
| 5 | `grid-cols-2` (ultimo span-2) | `grid-cols-3` (ultimos 2 + 3) | `grid-cols-5` |
| 6+ | `grid-cols-2` | `grid-cols-3` | `grid-cols-3` ou carousel |

**Regras:**
- PROIBIDO terminar com card impar solitario na ultima linha.
- KpiGrid auto-detecta count e aplica a melhor distribuicao.
- Cada card tem altura uniforme (`<KpiCard>` aplica `h-full`).

## 3. Tabelas

Componente obrigatorio: `<ResponsiveTable>` (Components/Organisms/Table/ResponsiveTable.vue).

| Viewport | Render | Notas |
|----------|--------|-------|
| Desktop (>=1024) | `<table>` completa | Todas as colunas, sticky col1 + colN-actions |
| Tablet (768-1023) | `<table>` reduzida | Apenas `priorityColumns`, link "Ver mais" abre drawer |
| Mobile (<768) | Cards via `<TableMobileCard>` | 2-3 dados-chave por card, tap abre detalhes |

**Regras:**
- PROIBIDO: `<table>` direto fora de `<ResponsiveTable>`.
- PROIBIDO: `overflow-x-auto` como unica estrategia mobile (corta dados, vide bug Compdec/Orgaos).
- Excecao permitida: dashboards com tabelas pequenas (<=4 colunas, todas curtas) podem usar `<table>` simples com `class="text-sm"`.

## 4. Filtros

Dois componentes irmaos com mesma API:

- `<FilterPanel>` (Web): sidebar lateral colapsavel em desktop, accordion em tablet.
- `<FilterBottomSheet>` (Mobile): drawer slide-up via chip "Filtros".

```vue
<!-- Uso recomendado -->
<FilterPanel v-if="!isMobile" :filters="filters" v-model="values" />
<FilterBottomSheet v-else :filters="filters" v-model="values" />
```

**Regras:**
- Filtros nunca consomem mais que 280px de largura em sidebar desktop.
- Em mobile, bottom sheet trava scroll do body (`@vueuse/core useScrollLock`).
- Botoes "Aplicar" e "Limpar" sempre presentes.

## 5. Page Header

Componente obrigatorio: `<PageHeader>` (Components/Organisms/PageHeader.vue).

```vue
<PageHeader
  title="Protocolos PAE"
  description="Gerencie os protocolos de analise de PAE"
  :breadcrumb="['Inicio', 'PAE']"
  :actions="[
    { label: 'Exportar', icon: 'download', variant: 'secondary' },
    { label: 'Novo Protocolo', icon: 'plus', variant: 'primary' }
  ]"
/>
```

**Regras:**
- Mobile: titulo medio + botao primario fixo + 3-dot menu se >2 acoes.
- Desktop: titulo grande + descricao + todas as acoes a direita.
- Breadcrumb sempre presente quando profundidade > 1.

## 6. Formularios

| Viewport | Layout | Labels |
|----------|--------|--------|
| Mobile | Stack vertical, full width | Acima do input |
| Tablet | Stack vertical OU 2-col se cabe | Acima do input |
| Desktop | 2-col grid (`grid-cols-1 md:grid-cols-2`) | Acima ou a esquerda |

**Regras:**
- Inputs em mobile tem `min-h-12` (48px) para touch target.
- Botoes de form em mobile ficam fixos no rodape (`sticky bottom-0`) em forms longos.
- Validacao inline (nao alert/modal) em mobile e desktop.

## 7. Acoes Destrutivas

| Viewport | Padrao |
|----------|--------|
| Desktop | `<ConfirmModal>` centrado |
| Mobile | `<ActionSheet>` bottom drawer com botao destrutivo destacado |

**Regras:**
- NUNCA usar `confirm()` ou `alert()` nativos.
- Botao destrutivo sempre com cor `red-600` e nunca como acao primaria default.
- Texto do botao explicito: "Excluir Protocolo", nao "OK" ou "Confirmar".

## 8. Touch Targets

Toda area interativa em mobile tem minimo 44x44px (iOS HIG).

Tokens Tailwind disponiveis em `tailwind.config.js`:

```js
spacing: {
  'touch-min': '44px',  // iOS HIG minimum
  'touch-rec': '48px',  // Material recommended
}
```

**Regras:**
- Botoes-icone em mobile: `min-h-touch-min min-w-touch-min`.
- Links de navegacao em listas: `py-3` minimo.
- Checkboxes/radios em mobile: escalar para 24px (`h-6 w-6`).

## 9. Safe Areas (iOS notch / Android navigation bar)

Layout principal respeita safe areas:

```vue
<NavigationHeader :style="{
  marginTop: `calc(max(env(safe-area-inset-top, 0px), var(--inset-top, 12px)) + ${isMobile ? '3rem' : '4rem'})`
}" />
```

**Regras:**
- Header sempre usa `env(safe-area-inset-top)`.
- Footer/bottom-nav usa `env(safe-area-inset-bottom)`.
- Conteudo scrollavel nunca atinge as safe areas.

## 10. Modais e Drawers

| Viewport | Render |
|----------|--------|
| Mobile | Full-width (`inset-x-0`), bottom-anchored ou fullscreen |
| Tablet | Centrado, `max-w-xl` |
| Desktop | Centrado, `max-w-3xl` para forms longos |

**Regras:**
- Modal em mobile abre como bottom sheet ou fullscreen, nunca centrado pequeno.
- `useScrollLock` ativo enquanto aberto.
- Botao fechar sempre visivel (canto superior direito).

## 11. Skeleton Loading

Componente: `<ContentAreaSkeleton>` (Components/Molecules/Skeleton/).

**Regras:**
- `usePageLoading.isPageLoading` reseta em `router.on('finish')`.
- Timeout maximo: 10 segundos (apos isso, mostrar erro).
- Skeleton nunca pode "travar" - bug atual em Fase 0.

## 12. Como verificar conformidade

```bash
# Lint custom (falha em <table> sem ResponsiveTable)
npm run lint

# Debug overlay em dev
# Abrir qualquer pagina com ?debug=responsive
# Mostra viewport, breakpoint e violacoes em tempo real

# Snapshots visuais multi-viewport
npx playwright test tests/visual/

# Audit catalogado por modulo
cat docs/superpowers/audits/2026-05-15-responsive-diff.md
```

## 13. Modulo referencia

`resources/js/Pages/Admin/Permissions/Users/Index.vue` e a implementacao de referencia. Ja usa `ResponsiveTable` + `useMobile()`. Novos modulos devem espelhar este padrao.

## 14. Quando este contrato evolui

Mudancas neste contrato exigem:
1. PR dedicado com label `design-system`
2. Aprovacao de 2 reviewers
3. Atualizacao de TODOS os modulos conformes
4. Atualizacao deste documento

Nunca deixar dois padroes coexistindo "temporariamente".
