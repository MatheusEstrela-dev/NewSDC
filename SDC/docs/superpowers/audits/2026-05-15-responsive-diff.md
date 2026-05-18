# Catalogo de Divergencias de Responsividade entre Modulos

> **Data:** 2026-05-15 (auditoria), revisado 2026-05-18 (Fase 0)
> **Branch:** feat/responsive-contract-fase-0
> **Referencia oficial:** `resources/js/Pages/Admin/Permissions/Users/Index.vue`

## Resumo Executivo

- **12 de 13 modulos** nao usam `useMobile()`, `ResponsiveTable` ou `TableMobileCard` em suas paginas Index.
- **1 modulo de referencia**: `Admin/Permissions/Users/Index.vue` (5 ocorrencias dos componentes do design-system).
- **8 Organisms** de tabela usam apenas `overflow-x-auto` como estrategia mobile - cria scroll horizontal cortando colunas (bug visivel em Compdec/Orgaos).
- **1 Organism** usa `useMobile()` corretamente: `Rat/Table/RatTable.vue` (3 ocorrencias).

## Tabela de Conformidade - Pages Index

| Modulo | Index Page | LOC | useMobile | TableMobileCard | ResponsiveTable | Status |
|--------|-----------|-----|-----------|------------------|-------------------|--------|
| RAT | `RatIndex.vue` | 69 | 0 | 0 | 0 | divergente |
| PAE | `PaeProtocolosIndex.vue` | 48 | 0 | 0 | 0 | divergente |
| Decretacoes | `Decretacoes/ProcessoIndex.vue` | 95 | 0 | 0 | 0 | divergente |
| Demandas | `Demandas/DemandasIndex.vue` | 36 | 0 | 0 | 0 | divergente |
| Compdec | `Compdec/OrgaosIndex.vue` | 355 | 0 | 0 | 0 | bug overflow critico |
| Inventario | `Inventario/InventarioIndex.vue` | 66 | 0 | 0 | 0 | divergente |
| Cisterna | `Cisterna/Index.vue` | 22 | 0 | 0 | 0 | divergente |
| Plantao | `Plantao/PlantaoIndex.vue` | 66 | 0 | 0 | 0 | divergente |
| PlanCon | `PlanCon/PlanConIndex.vue` | 81 | 0 | 0 | 0 | divergente |
| Treinamento | `Treinamento/TreinamentoIndex.vue` | 74 | 0 | 0 | 0 | divergente |
| AjudaHumanitaria | `AjudaHumanitaria/Beneficiarios/BeneficiarioIndex.vue` | 122 | 0 | 0 | 0 | divergente |
| Admin/Permissions | `Admin/Permissions/Users/Index.vue` | 354 | SIM | SIM | NAO* | **REFERENCIA** |

*Permissions usa `TableMobileCard` diretamente em vez de `ResponsiveTable` - vamos consolidar.

## Tabela de Conformidade - Organisms

| Modulo | Organism | useMobile | overflow-x-auto | ResponsiveTable | Status |
|--------|----------|-----------|------------------|-------------------|--------|
| RAT | `Rat/Table/RatTable.vue` | **3** | 1 | 0 | parcial (usa useMobile mas nao ResponsiveTable) |
| Decretacoes | `Decretacoes/ProcessoTable.vue` | 0 | 1 | 0 | divergente |
| Compdec | `Compdec/CompdecTabs.vue` | 0 | 1 | 0 | bug overflow critico |
| Compdec | `Compdec/AnexosTable.vue` | 0 | 0 | 0 | sem protecao |
| Compdec | `Compdec/EquipeTable.vue` | 0 | 0 | 0 | sem protecao |
| Compdec | `Compdec/PlanosTable.vue` | 0 | 0 | 0 | sem protecao |
| Inventario | `Inventario/InventarioTable.vue` | 0 | 1 | 0 | divergente |
| Cisterna | `Cisterna/CisternaTable.vue` | 0 | 1 | 0 | divergente |
| Plantao | `Plantao/PlantaoTable.vue` | 0 | 1 | 0 | divergente |
| Estoque | `Estoque/EstoqueTable.vue` | 0 | sim | 0 | divergente |

## Tabela de Conformidade - Outros (Filtros / Stats / Grids)

| Modulo | Componente | Padrao usado | Conforme contrato? |
|--------|-----------|--------------|---------------------|
| Compdec | `OrgaosFiltersSection.vue` | proprio | NAO (precisa FilterPanel/BottomSheet) |
| Compdec | `OrgaoStatsCards.vue` | proprio | NAO (precisa KpiGrid) |
| Treinamento | `TreinamentoFiltersSection.vue` | proprio | NAO |
| Treinamento | `TreinamentoStatsCards.vue` | proprio | NAO |
| Treinamento | `TreinamentoGrid.vue` | proprio | NAO |
| AjudaHumanitaria | `BeneficiarioFiltersSection.vue` | proprio | NAO |
| AjudaHumanitaria | `BeneficiarioStatsCards.vue` | proprio | NAO |
| AjudaHumanitaria | `BeneficiarioGrid.vue` | proprio | NAO |
| PlanCon | `PlanConDashboardGrid.vue` | proprio | NAO |

## Padroes Divergentes Identificados

### Padrao A: "Tabela com overflow-x-auto" (8 modulos)
Envolve `<table>` em `<div class="overflow-x-auto">`. Funciona em desktop, mas em mobile gera scroll horizontal que **corta** colunas (bug Compdec/Orgaos confirmado).

### Padrao B: "Tabela sem protecao" (3 Organisms Compdec)
`<table>` direto, sem overflow nem responsividade. Quebra completamente em mobile.

### Padrao C: "Stats cards proprios" (todos exceto Permissions)
Cada modulo tem seu `XStatsCards.vue` com grid hardcoded. Distribuicao inconsistente entre modulos.

### Padrao D: "Filtros proprios" (todos exceto Permissions)
Cada modulo tem seu `XFiltersSection.vue`. UX divergente entre modulos.

### Padrao E: "RAT parcial" (1 modulo)
`RatTable.vue` usa `useMobile()` e tem renders condicionais, mas nao usa o `<ResponsiveTable>` oficial. Implementacao "quase certa, mas custom".

### Padrao F (referencia): "Permissions completo" (1 modulo)
`Admin/Permissions/Users/Index.vue` usa `useMobile()` + `TableMobileCard` + `TableActions`. E nosso ponto de partida.

## Plano de Convergencia (mapeamento para Fase 8)

Cada divergencia vira tarefa nas subfases 8.1-8.12:

| Modulo | Tarefas Web | Tarefas Mobile | Subfase |
|--------|-------------|----------------|---------|
| RAT | Migrar `RatTable.vue` para usar `<ResponsiveTable>` | Confirmar `RatCard` em mobile | 8.1 |
| PAE | Criar `<ResponsiveTable>` para protocolos | Cards mobile + ActionSheet | 8.2 |
| Decretacoes | Migrar `ProcessoTable.vue` para `<ResponsiveTable>` | Cards com chip ECP/SE | 8.3 |
| Compdec | Migrar `CompdecTabs.vue` + `AnexosTable` + `EquipeTable` + `PlanosTable` | Fix overflow critico (Orgaos) | 8.4 |
| Demandas | Criar Organism de tabela com `<ResponsiveTable>` | Cards com SLA color-coded | 8.5 |
| TDAP | Migrar 9 sub-tabelas | TdapHub mobile | 8.6 |
| AjudaHumanitaria | Migrar `BeneficiarioGrid` | Cards avatar + wizard | 8.7 |
| Inventario | Migrar `InventarioTable.vue` | Cards com thumbnail | 8.8 |
| Plantao | Migrar `PlantaoTable.vue` | Timeline + FAB | 8.9 |
| PlanCon | Migrar `PlanConDashboardGrid` | Cards | 8.11 |
| Treinamento | Migrar `TreinamentoGrid` | Cards de cursos | 8.11 |
| Cisterna | Migrar `CisternaTable.vue` | Lista geo | 8.11 |

## Estimativa de Migracao

- 12 modulos x ~1.5h cada = **18h** para migrar Index pages
- 10 Organisms x ~1h cada = **10h** para migrar tabelas
- 9 Stats/Filters/Grids x ~0.5h cada = **4.5h**
- **Total Fase 8**: ~32.5h (consistente com estimativa de 16h do plano + 16h de buffer)

## Validacao Pos-Migracao

- [ ] `npm run lint` passa (ESLint rule `no-bare-table` nao falha)
- [ ] Playwright em viewport 375px: nenhum overflow horizontal em nenhum modulo
- [ ] Playwright em viewport 768px: layout consistente entre modulos
- [ ] Diff visual lado-a-lado: dois modulos diferentes no mesmo viewport tem mesma estrutura visual (header, KPIs, tabela/cards, filtros)
- [ ] `ResponsiveDebugOverlay` (em `?debug=responsive`) nao acusa violacoes em nenhuma pagina

## Riscos da Migracao

| Risco | Mitigacao |
|-------|-----------|
| `RatTable` ja tem logica custom de useMobile - migrar pode regredir comportamento | Snapshot Playwright antes; manter testes E2E de RAT verde |
| `CompdecOrgaosIndex` tem 355 LOC - pode esconder logica nao-trivial | Refactor em PR dedicado; nao bundlar com outros modulos |
| Migracao em paralelo (1 modulo por PR) cria conflitos no `ResponsiveTable.vue` | Estabilizar `ResponsiveTable` na Fase 0 antes de iniciar Fase 8 |
| Acoes inline custom (cores, icones, tooltips) variam entre modulos | `<TableActions>` aceita slot custom para preservar identidade visual de cada modulo |
