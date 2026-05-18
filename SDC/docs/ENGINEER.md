# ENGINEER.md — Padrão Atomic Design (Frontend SDC)

Documento de referência rápida para o frontend Vue/Inertia do SDC. Define o padrão de componentização que **todos os módulos novos devem seguir** e que os módulos legados devem migrar gradualmente.

## 1. Pirâmide

O frontend segue Atomic Design simplificado em três níveis, com Páginas separadas:

```
Pages (Inertia)            resources/js/Pages/[Modulo]/[Recurso]/Index|Create|Edit|Show.vue
  └── usa ─►
Organisms                  resources/js/Components/Organisms/[ ... ]
  └── usa ─►
Molecules                  resources/js/Components/Molecules/[ ... ]
  └── usa ─►
Atoms                      resources/js/Components/Atoms/[ ... ]
```

**Atoms** — peça mínima e reutilizável; não conhece domínio.
Exemplos: `Atoms/Button/Button.vue`, `Atoms/Input/TextInput.vue`, `Atoms/Badge/StatusBadge.vue`, `Atoms/Card/CardBase.vue`, `Atoms/Typography/Heading.vue`.

**Molecules** — composição de atoms com pequena lógica de UI.
Exemplos: `Molecules/Form/FormField.vue`, `Molecules/Filter/FilterSection.vue` + `FilterField.vue` + `FilterActions.vue`, `Molecules/Navigation/BreadcrumbTrail.vue`, `Molecules/Navigation/Pagination.vue`, `Molecules/Statistics/StatCard.vue`.

**Organisms** — bloco de página com domínio; pode invocar serviços/composables.
Exemplos: `Organisms/PageHeader.vue`, `Organisms/Table/ResponsiveTable.vue`, `Organisms/ExportCsvModal.vue` e, por módulo, `Organisms/[Modulo]/...`.

**Pages** — orquestram organisms; consomem props de Inertia; ficam em `resources/js/Pages/`. Não fazem parte da pirâmide Atomic e **não** devem conter `<table>`, filtros ou stat cards inline. Se um pedaço de markup se repete em mais de uma página, ele vira organism.

**Layouts** — `resources/js/Layouts/`. Cada módulo grande pode ter um layout dedicado (ex.: `TdapLayout.vue`) que substitui a `Sidebar` global por uma sub-navegação específica.

## 2. Convenções de nome

- **PascalCase** sempre. Sem prefixos `A`/`M`/`O` no nome do arquivo.
- Componentes específicos de módulo levam o **prefixo do módulo** + ficam em sub-pasta com o nome do módulo:
  - `Organisms/Decretacoes/ProcessoTable.vue` (sem prefixo na pasta do dono — Decretacoes)
  - `Organisms/Pae/Protocolos/PaeProtocolosTable.vue`
  - `Organisms/Tdap/TdapPrestadoresFiltersSection.vue`
- Páginas: pasta singular do módulo → recurso plural → arquivo PascalCase
  - `Pages/Tdap/Atas/Index.vue`, `Pages/Tdap/Atas/Create.vue`.

## 3. Gold-standard (modelos a copiar)

Quando criar um novo recurso, espelhe um destes módulos:

- **Decretacoes** — `resources/js/Components/Organisms/Decretacoes/` (ProcessoForm, ProcessoTable, ProcessoFilters, ProcessoGrid, ProcessoCard, DecretacoesStatsCards).
- **Pae** — `resources/js/Components/Organisms/Pae/Protocolos/` (PaeProtocolosStatsCards, PaeProtocolosFilters, PaeProtocolosTable, PaeProtocolosGrid).

## 4. Checklist por novo módulo

Para cada módulo, espera-se a paleta:

- [ ] `{Modulo}PageHeader.vue` — header padrão com icon + título + descrição + slot `actions`.
- [ ] `{Modulo}StatsRow.vue` (ou `{Modulo}StatsCards.vue`) — grid de cards numéricos.
- [ ] `{Modulo}{Recurso}FiltersSection.vue` por recurso — wrap de `FilterSection` + `FilterField` + `FilterActions`.
- [ ] `{Modulo}DataTable.vue` — wrapper de `Organisms/Table/ResponsiveTable.vue` com header + paginação padronizada, slots `columns`/`row`/`empty`.
- [ ] `{Modulo}{Recurso}Form.vue` — composição de `Molecules/Form/*` + `Molecules/Filter/*`.
- [ ] `{Modulo}StatusBadge.vue` (ou `Estado{X}Badge.vue`) — átomo de status do domínio.
- [ ] (se tiver navegação própria) `{Modulo}Sidebar.vue` + `{Modulo}Layout.vue`.

## 5. Anti-padrões (não fazer)

- `<table>` cru, filtros `<input>/<select>` soltos, stat-cards `<div class="...4 divs idênticos">` dentro de páginas.
- Lógica de negócio em Atoms (Atoms devem ser puros e sem dependência de domínio).
- Componentes de módulo na raiz de `Atoms/`, `Molecules/` ou `Organisms/` sem sub-pasta do módulo.
- Misturar Blade + Vue na mesma página (todas as páginas TDAP/PAE/Decretacoes são Inertia/Vue).

## 6. Migração gradual

- Páginas existentes podem continuar com `AuthenticatedLayout` enquanto não forem migradas. A nova paleta convive com o legado.
- Refactor por ondas: começar pelas páginas de Index do módulo (maior impacto visual e mais duplicação).
