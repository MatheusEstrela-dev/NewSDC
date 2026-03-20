# Plano de Melhoria: Filtros Decretacoes NewSDC

## Objetivo
Melhorar a implementacao do filtro de busca no NewSDC baseado na funcionalidade do sistema legado (sdc), respeitando o design ja implementado, SOLID, Clean Code e patterns existentes.

---

## Analise Comparativa

### Funcionalidades do Legado (sdc) que faltam no NewSDC:

| Funcionalidade | Legado | NewSDC | Gap |
|----------------|--------|--------|-----|
| Filtro COBRADE Hierarquico | Sim | Nao | CRITICO |
| Botoes Rapidos COBRADE | Sim (BIOLOGICO, CHUVA, etc) | Nao | ALTO |
| Multi-select Status | Sim | Parcial | MEDIO |
| Filtro por Analista | Sim | Nao | MEDIO |
| Filtro Situacao Anormalidade | Sim | Nao | MEDIO |
| Data Decreto Range | Sim | Nao | MEDIO |
| Filtro Municipio com Contagem | Sim | Parcial | BAIXO |
| Filtro Protocolo FIDE | Sim | Parcial | BAIXO |
| Active Filters com Remove | Sim | Nao | ALTO |
| Export com Filtros | Sim | Parcial | MEDIO |

---

## Plano de Implementacao

### Fase 1: Backend - Filtros Avancados (ProcessoFilter.php)

**Arquivo:** `app/Modules/Decretacoes/Filters/ProcessoFilter.php`

**Tarefas:**
1. Adicionar metodo `filterByTipoDesastre()` para filtrar por COBRADE IDs (csv multi-select)
2. Adicionar metodo `filterByAnalista()` para filtrar por analista
3. Adicionar metodo `filterBySituacaoAnormalidade()` (N1, SE, ECP)
4. Adicionar metodo `filterByDataDecretoRange()` para data_decreto_inicio e data_decreto_fim
5. Atualizar `getFilterOptions()` para retornar:
   - Lista de analistas (distinct)
   - Lista de situacoes de anormalidade
   - Lista completa de tipos de desastre (COBRADE)

### Fase 2: Backend - Service Layer

**Arquivo:** `app/Modules/Decretacoes/Services/ProcessoQueryService.php`

**Tarefas:**
1. Atualizar `FILTER_PARAMS` para incluir novos parametros
2. Atualizar `getActiveFiltersSummary()` para labels humanos dos novos filtros
3. Garantir que `getFilterOptions()` retorne dados para todos os dropdowns

### Fase 3: Frontend - Componentes de Filtro COBRADE

**Novos Arquivos:**
- `Components/Molecules/Filter/CobradeFilter.vue` - Componente hierarquico COBRADE
- `Components/Molecules/Filter/CobradeQuickButtons.vue` - Botoes rapidos
- `Components/Molecules/Filter/ActiveFilters.vue` - Tags de filtros ativos

**Estrutura do CobradeFilter.vue:**
```
- Props: cobradeList, modelValue (array de IDs)
- Emits: update:modelValue
- Features:
  - Grupos colapsaveis (Grupo -> Subgrupo -> Tipo -> Subtipo)
  - Multi-select com checkboxes
  - Contador de selecionados
  - Busca por nome/codigo
```

**Estrutura do CobradeQuickButtons.vue:**
```
- Props: quickFilters (mapeamento predefinido)
- Emits: select
- Botoes: BIOLOGICO, CHUVA, OUTROS, SECA, TECNOLOGICO
- Cada botao seleciona conjunto de IDs predefinidos
```

**Estrutura do ActiveFilters.vue:**
```
- Props: activeFilters (array de {key, label, value})
- Emits: remove, clear-all
- Exibe badges com botao X para cada filtro ativo
```

### Fase 4: Frontend - Atualizacao ProcessoFilters.vue

**Arquivo:** `Components/Organisms/Decretacoes/ProcessoFilters.vue`

**Tarefas:**
1. Adicionar secao de Filtros Avancados (colapsavel)
2. Integrar CobradeFilter e CobradeQuickButtons
3. Adicionar campos:
   - Analista (select)
   - Situacao de Anormalidade (select: N1, SE, ECP)
   - Data Decreto Inicio/Fim (date range)
4. Integrar ActiveFilters no topo
5. Manter layout responsivo existente

### Fase 5: Composable para Logica de Filtros

**Novo Arquivo:** `composables/useDecretacoesFilters.ts`

**Funcionalidades:**
```typescript
- filters: reactive state
- activeFilters: computed (lista de filtros ativos com labels)
- activeFiltersCount: computed
- clearFilter(key): remove filtro especifico
- clearAllFilters(): limpa todos
- applyFilters(): submete filtros via Inertia
- toggleCobradeGroup(groupId): expande/colapsa grupo
- selectCobradeQuick(type): seleciona preset COBRADE
```

### Fase 6: Integracao e Testes

**Tarefas:**
1. Atualizar ProcessoIndexTemplate.vue para usar novo composable
2. Atualizar ProcessoIndex.vue para receber filterOptions completo
3. Atualizar DecretacoesController@index para enviar dados COBRADE
4. Testar todos os filtros individualmente
5. Testar combinacoes de filtros
6. Testar responsividade mobile

---

## Arquivos a Modificar

### Backend:
1. `app/Modules/Decretacoes/Filters/ProcessoFilter.php`
2. `app/Modules/Decretacoes/Services/ProcessoQueryService.php`
3. `app/Modules/Decretacoes/Controllers/DecretacoesController.php`

### Frontend:
1. `resources/js/Components/Organisms/Decretacoes/ProcessoFilters.vue`
2. `resources/js/Components/Templates/Decretacoes/ProcessoIndexTemplate.vue`
3. `resources/js/Pages/Decretacoes/ProcessoIndex.vue`

### Novos Arquivos:
1. `resources/js/Components/Molecules/Filter/CobradeFilter.vue`
2. `resources/js/Components/Molecules/Filter/CobradeQuickButtons.vue`
3. `resources/js/Components/Molecules/Filter/ActiveFilters.vue`
4. `resources/js/composables/useDecretacoesFilters.ts`

---

## Dados COBRADE (Mapeamento Rapido)

```javascript
const COBRADE_QUICK_FILTERS = {
  BIOLOGICO: [34, 35, 36, 38, 41],
  CHUVA: [4, 5, 6, 7, 8, 9, 10, 42, 11, 14, 15, 16, 17, 21, 22, 23, 24, 25],
  OUTROS: [1, 19, 20, 26, 27, 28, 33],
  SECA: [29, 30, 31, 32],
  TECNOLOGICO: [45, 47, 49, 51, 54, 56, 57, 58, 59, 60, 61]
}
```

---

## Ordem de Execucao

1. **Fase 1** - Backend: ProcessoFilter.php (base para tudo)
2. **Fase 2** - Backend: ProcessoQueryService.php
3. **Fase 5** - Composable useDecretacoesFilters (logica reutilizavel)
4. **Fase 3** - Frontend: Componentes moleculares (CobradeFilter, ActiveFilters)
5. **Fase 4** - Frontend: ProcessoFilters.vue (integracao)
6. **Fase 6** - Integracao final e testes

---

## Consideracoes SOLID

- **S (Single Responsibility):** Cada componente de filtro tem uma unica responsabilidade
- **O (Open/Closed):** CobradeFilter extensivel para novos grupos sem modificar codigo
- **L (Liskov):** Componentes de filtro sao intercambiaveis
- **I (Interface Segregation):** Props minimas e especificas para cada componente
- **D (Dependency Inversion):** Composables abstraem logica de estado

---

## Estimativa de Complexidade

| Fase | Complexidade | Arquivos |
|------|--------------|----------|
| Fase 1 | Media | 1 |
| Fase 2 | Baixa | 1 |
| Fase 3 | Alta | 3 |
| Fase 4 | Media | 1 |
| Fase 5 | Media | 1 |
| Fase 6 | Media | 3 |

**Total:** 10 arquivos (4 novos, 6 modificados)
