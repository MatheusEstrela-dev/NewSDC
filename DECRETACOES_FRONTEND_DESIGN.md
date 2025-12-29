# Planejamento Frontend: Sistema de Decretações - NewSDC

## 1. Princípios de Design

### 1.1 Design System Base
Seguir o design system já estabelecido no NewSDC:
- **Cores**: Paleta definida em `tailwind.config.js`
- **Tipografia**: System fonts otimizadas
- **Espaçamento**: Sistema de 4px base
- **Componentes**: Atomic Design (Atoms → Molecules → Organisms → Templates → Pages)

### 1.2 Filosofia UX

**Antes (Sistema Legado)**:
- Formulários longos em tabelas HTML
- Muita informação de uma vez
- Difícil navegação em mobile
- Validação apenas no submit

**Depois (NewSDC)**:
- Progressive disclosure (revelação progressiva)
- Wizard multi-step
- Mobile-first responsive
- Validação em tempo real
- Autosave de rascunhos

---

## 2. Arquitetura de Componentes

### 2.1 Estrutura de Diretórios

```
SDC/resources/js/
├── Pages/
│   └── Decretacoes/
│       ├── ProcessoIndex.vue
│       ├── ProcessoCreate.vue
│       ├── ProcessoEdit.vue
│       ├── ProcessoShow.vue
│       └── ProcessoPrint.vue
│
├── Templates/
│   └── Decretacoes/
│       ├── ProcessoIndexTemplate.vue
│       ├── ProcessoFormTemplate.vue
│       └── ProcessoShowTemplate.vue
│
├── Organisms/
│   └── Decretacoes/
│       ├── ProcessoWizard/
│       │   ├── ProcessoWizard.vue
│       │   ├── WizardStep.vue
│       │   └── WizardNavigation.vue
│       ├── ProcessoTable/
│       │   ├── ProcessoTable.vue
│       │   ├── ProcessoTableRow.vue
│       │   └── ProcessoTableFilters.vue
│       ├── ProcessoCard/
│       │   ├── ProcessoCard.vue
│       │   └── ProcessoCardActions.vue
│       ├── DadosDesastre/
│       │   ├── DadosHumanosForm.vue
│       │   ├── DadosMateriaisForm.vue
│       │   ├── PrejuizosForm.vue
│       │   └── DadosDesastreGrid.vue
│       └── ProcessoTimeline/
│           ├── StatusTimeline.vue
│           └── TimelineItem.vue
│
├── Molecules/
│   └── Decretacoes/
│       ├── Inputs/
│       │   ├── COBRADESelect.vue
│       │   ├── ProtocoloFIDEInput.vue
│       │   ├── MunicipioSelect.vue
│       │   └── TipoProcessoToggle.vue
│       ├── Cards/
│       │   ├── ProcessoMiniCard.vue
│       │   ├── StatusCard.vue
│       │   └── PrazoCard.vue
│       ├── Badges/
│       │   ├── StatusBadge.vue
│       │   ├── PrazoBadge.vue
│       │   └── TipoDesastreBadge.vue
│       └── Maps/
│           ├── MunicipioMap.vue
│           └── AreaDesastreDrawer.vue
│
└── Composables/
    └── Decretacoes/
        ├── useProcessoForm.js
        ├── useProcessoFilters.js
        ├── useCOBRADE.js
        ├── useStatusTimeline.js
        └── useProcessoAutosave.js
```

---

## 3. Páginas Principais

### 3.1 ProcessoIndex.vue - Lista de Processos

#### Layout Proposto

```
┌─────────────────────────────────────────────────────────────┐
│  [🏠 Home] > [Decretações]                    [👤 User ▼]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📋 Reconhecimentos de Desastre                             │
│                                                             │
│  ┌────────────────────────────────────────────────────────┐│
│  │ 🔍 Buscar processos...              [🎯 Filtros (3)] │││
│  └────────────────────────────────────────────────────────┘│
│                                                             │
│  ┌─────────┐ ┌─────────┐ ┌──────────┐ ┌──────────┐        │
│  │  Total  │ │ Vigentes│ │  Vencidos│ │Próx.Venc.│        │
│  │   156   │ │    89   │ │    45    │ │    22    │        │
│  └─────────┘ └─────────┘ └──────────┘ └──────────┘        │
│                                                             │
│  [+ Novo Processo]              [⬇ Exportar] [🖨 Imprimir] │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 🏷️ ESTADUAL  ⓘ Enchente (1.2.1.1.0)   📍 5 municípios │  │
│  │ Protocolo: 12345-2024        Status: 🟢 Reconhecido   │  │
│  │ Data: 15/12/2024             Prazo: 45 dias restantes │  │
│  │                                                        │  │
│  │ Municípios: Belo Horizonte, Contagem, Betim...        │  │
│  │ Danos: 150 desabrigados, 5 feridos                    │  │
│  │                                    [👁️ Ver] [✏️ Editar] │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  [...mais cards...]                                         │
│                                                             │
│  [← 1 2 3 ... 10 →]                                        │
└─────────────────────────────────────────────────────────────┘
```

#### Componentes

```vue
<template>
  <ProcessoIndexTemplate>
    <template #header>
      <PageHeader
        title="Reconhecimentos de Desastre"
        icon="document-text"
      >
        <template #actions>
          <Button
            @click="router.visit(route('decretacoes.create'))"
            variant="primary"
          >
            <PlusIcon /> Novo Processo
          </Button>
        </template>
      </PageHeader>
    </template>

    <template #filters>
      <ProcessoTableFilters
        v-model:filters="filters"
        :stats="stats"
        @update:filters="handleFilterChange"
      />
    </template>

    <template #stats>
      <StatsGrid :stats="stats" @click="handleStatClick" />
    </template>

    <template #content>
      <ProcessoTable
        :processos="processos.data"
        :loading="loading"
        @view="handleView"
        @edit="handleEdit"
        @delete="handleDelete"
      />

      <Pagination
        :meta="processos.meta"
        @change="handlePageChange"
      />
    </template>
  </ProcessoIndexTemplate>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useProcessoFilters } from '@/Composables/Decretacoes/useProcessoFilters'

const props = defineProps({
  processos: Object,
  stats: Object,
  filterOptions: Object
})

const { filters, handleFilterChange } = useProcessoFilters()
const loading = ref(false)

const handleStatClick = (stat) => {
  // Aplicar filtro rápido baseado no stat clicado
  if (stat === 'vigentes') {
    filters.vigencia_status = 'vigente'
  }
}
</script>
```

#### Filtros Avançados (Sidebar/Modal)

```vue
<FilterSection title="Filtros Avançados" collapsible>
  <FilterGroup label="Período">
    <DateRangePicker
      v-model:start="filters.data_entrada_inicio"
      v-model:end="filters.data_entrada_fim"
    />
  </FilterGroup>

  <FilterGroup label="Tipo de Processo">
    <RadioGroup
      v-model="filters.processo"
      :options="[
        { value: 'ESTADUAL', label: 'Estadual' },
        { value: 'MUNICIPAL', label: 'Municipal' }
      ]"
    />
  </FilterGroup>

  <FilterGroup label="Tipo de Desastre">
    <COBRADESelect
      v-model="filters.tipo_desastre_id"
      placeholder="Selecione o tipo"
    />
  </FilterGroup>

  <FilterGroup label="Status">
    <MultiSelect
      v-model="filters.reconhecimento"
      :options="statusOptions"
      placeholder="Todos os status"
    />
  </FilterGroup>

  <FilterGroup label="Município">
    <MunicipioSelect
      v-model="filters.municipio_id"
      placeholder="Todos os municípios"
    />
  </FilterGroup>

  <FilterGroup label="Vigência">
    <Select
      v-model="filters.vigencia_status"
      :options="[
        { value: 'todos', label: 'Todos' },
        { value: 'vigente', label: 'Vigentes' },
        { value: 'vencido', label: 'Vencidos' },
        { value: 'proximo_vencer', label: 'Próximos ao vencimento' }
      ]"
    />
  </FilterGroup>

  <div class="flex gap-2 mt-4">
    <Button @click="applyFilters" variant="primary" full>
      Aplicar Filtros
    </Button>
    <Button @click="clearFilters" variant="outline">
      Limpar
    </Button>
  </div>
</FilterSection>
```

---

### 3.2 ProcessoCreate.vue - Wizard Multi-Step

#### Layout Proposto

```
┌─────────────────────────────────────────────────────────────┐
│  [🏠 Home] > [Decretações] > [Novo Processo]                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──[1]───────[2]───────[3]───────[4]───────[5]───────[6]─┐│
│  │   ●───────●───────●───────○───────○───────○           ││
│  │ Básico  Municípios  Mapa  Danos H. Danos M. Revisão   ││
│  └──────────────────────────────────────────────────────────┘│
│                                                             │
│  ┌────────────────────────────────────────────────────────┐│
│  │                                                        ││
│  │  📍 Step 1: Dados Básicos                             ││
│  │                                                        ││
│  │  Tipo do Desastre *                                   ││
│  │  [🔍 Buscar COBRADE...                            ▼]  ││
│  │                                                        ││
│  │  Código COBRADE                                        ││
│  │  [1.2.1.1.0 - Enchentes] (readonly)                   ││
│  │                                                        ││
│  │  ┌────────────────┐  ┌────────────────┐              ││
│  │  │ Data Entrada   │  │ Data Ocorrência│              ││
│  │  │ [15/12/2024]   │  │ [14/12/2024]   │              ││
│  │  └────────────────┘  └────────────────┘              ││
│  │                                                        ││
│  │  Origem do Processo *                                 ││
│  │  ( ) Estadual  (●) Municipal                          ││
│  │                                                        ││
│  │  Status *                                              ││
│  │  [Registro                                        ▼]  ││
│  │                                                        ││
│  │  Analista Responsável                                 ││
│  │  [João Silva                                      ▼]  ││
│  │                                                        ││
│  │                          [Voltar] [Próximo: Municípios →]││
│  └────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

#### Step 2: Seleção de Municípios

```
┌────────────────────────────────────────────────────────┐
│  📍 Step 2: Municípios Afetados                        │
│                                                        │
│  [🔍 Buscar município...                          ▼]  │
│                                                        │
│  Municípios Selecionados:                             │
│  ┌──────────────────────────────────────────────────┐ │
│  │ 🏙️ Belo Horizonte              Redec: 01   [✕]  │ │
│  │ População: 2.530.701                             │ │
│  │                                                  │ │
│  │ 🏙️ Contagem                    Redec: 01   [✕]  │ │
│  │ População: 668.949                               │ │
│  └──────────────────────────────────────────────────┘ │
│                                                        │
│  💡 Dica: Em processos estaduais, você pode           │
│     selecionar múltiplos municípios                   │
│                                                        │
│                    [← Voltar] [Próximo: Mapa →]       │
└────────────────────────────────────────────────────────┘
```

#### Step 3: Localização no Mapa

```
┌────────────────────────────────────────────────────────┐
│  🗺️ Step 3: Área Afetada                               │
│                                                        │
│  ┌──────────────────────────────────────────────────┐ │
│  │                    MAPA                          │ │
│  │                                                  │ │
│  │         ┌─────────────┐                         │ │
│  │         │  Polígono   │                         │ │
│  │         │  Desenhado  │                         │ │
│  │         └─────────────┘                         │ │
│  │                                                  │ │
│  │  [🖊️ Desenhar Área] [🗑️ Limpar] [📐 Medir]      │ │
│  └──────────────────────────────────────────────────┘ │
│                                                        │
│  ✓ Área dentro do município: Belo Horizonte           │
│  📊 Área calculada: 125,4 km²                          │
│                                                        │
│  💡 Dica: Desenhe o polígono da área afetada          │
│     clicando no mapa                                   │
│                                                        │
│                 [← Voltar] [Próximo: Danos Humanos →] │
└────────────────────────────────────────────────────────┘
```

#### Step 4: Danos Humanos

```
┌────────────────────────────────────────────────────────┐
│  👥 Step 4: Danos Humanos                              │
│                                                        │
│  Por Município:                                        │
│  [Belo Horizonte ▼]                                   │
│                                                        │
│  ┌──────────────┬──────────────┬──────────────┐       │
│  │   Categoria  │  Quantidade  │  Observações │       │
│  ├──────────────┼──────────────┼──────────────┤       │
│  │ Óbitos       │ [____0_____] │ [_________]  │       │
│  │ Feridos      │ [____5_____] │ [_________]  │       │
│  │ Desabrigados │ [___150____] │ [_________]  │       │
│  │ Desalojados  │ [___300____] │ [_________]  │       │
│  │ Desaparecidos│ [____0_____] │ [_________]  │       │
│  │ Outros Afet. │ [___500____] │ [_________]  │       │
│  └──────────────┴──────────────┴──────────────┘       │
│                                                        │
│  Total de Pessoas Afetadas: 955                        │
│                                                        │
│  💡 Validação: Valores não podem exceder a             │
│     população do município (2.530.701)                 │
│                                                        │
│              [← Voltar] [Próximo: Danos Materiais →]  │
└────────────────────────────────────────────────────────┘
```

#### Step 5: Danos Materiais e Prejuízos

```
┌────────────────────────────────────────────────────────┐
│  🏠 Step 5: Danos Materiais e Prejuízos                │
│                                                        │
│  [Belo Horizonte ▼]                                   │
│                                                        │
│  Danos Materiais                                       │
│  ┌──────────────┬──────────┬──────────┬────────────┐  │
│  │   Item       │Destruídas│Danificadas│ Valor (R$)│  │
│  ├──────────────┼──────────┼──────────┼────────────┤  │
│  │ Residências  │ [__45__] │ [__120__]│[1.500.000]│  │
│  │ Comércios    │ [__12__] │ [__35__] │[  800.000]│  │
│  │ Escolas      │ [___0__] │ [___2__] │[  150.000]│  │
│  │ Postos Saúde │ [___0__] │ [___1__] │[  200.000]│  │
│  └──────────────┴──────────┴──────────┴────────────┘  │
│                                                        │
│  Prejuízos Econômicos                                  │
│  ┌──────────────┬──────────────┬───────────────────┐  │
│  │   Setor      │  Área (ha)   │   Prejuízo (R$)  │  │
│  ├──────────────┼──────────────┼───────────────────┤  │
│  │ Agricultura  │ [___500____] │ [____2.000.000__]│  │
│  │ Indústria    │ [___N/A____] │ [____1.000.000__]│  │
│  │ Comércio     │ [___N/A____] │ [______500.000__]│  │
│  └──────────────┴──────────────┴───────────────────┘  │
│                                                        │
│  Total Prejuízos: R$ 6.150.000,00                      │
│                                                        │
│                  [← Voltar] [Próximo: Revisão →]      │
└────────────────────────────────────────────────────────┘
```

#### Step 6: Revisão Final

```
┌────────────────────────────────────────────────────────┐
│  ✅ Step 6: Revisão e Confirmação                      │
│                                                        │
│  Revise os dados antes de submeter:                   │
│                                                        │
│  📋 Dados Básicos                              [✏️ Editar]│
│  ├─ Tipo: Enchente (COBRADE 1.2.1.1.0)                │
│  ├─ Origem: Municipal                                  │
│  ├─ Data Ocorrência: 14/12/2024                        │
│  └─ Status: Registro                                   │
│                                                        │
│  📍 Municípios                                 [✏️ Editar]│
│  └─ Belo Horizonte (Redec 01)                         │
│                                                        │
│  🗺️ Área Afetada                               [✏️ Editar]│
│  └─ 125,4 km² (polígono desenhado)                    │
│                                                        │
│  👥 Danos Humanos                              [✏️ Editar]│
│  ├─ Total Afetados: 955 pessoas                       │
│  ├─ Desabrigados: 150                                 │
│  └─ Desalojados: 300                                  │
│                                                        │
│  🏠 Danos Materiais                            [✏️ Editar]│
│  ├─ Residências Destruídas: 45                        │
│  ├─ Residências Danificadas: 120                      │
│  └─ Valor Total: R$ 2.650.000,00                      │
│                                                        │
│  💰 Prejuízos Econômicos                       [✏️ Editar]│
│  └─ Total: R$ 3.500.000,00                            │
│                                                        │
│  ⚠️ ATENÇÃO: Após submeter, o processo será           │
│     enviado para análise. Certifique-se que           │
│     todos os dados estão corretos.                     │
│                                                        │
│           [← Voltar] [✓ Submeter Processo]            │
└────────────────────────────────────────────────────────┘
```

#### Implementação do Wizard

```vue
<template>
  <ProcessoFormTemplate>
    <ProcessoWizard
      v-model:currentStep="currentStep"
      :steps="steps"
      :form="form"
      @submit="handleSubmit"
    >
      <template #step-1>
        <DadosBasicosForm v-model="form.dadosBasicos" />
      </template>

      <template #step-2>
        <MunicipiosForm
          v-model="form.municipios"
          :tipo-processo="form.dadosBasicos.processo"
        />
      </template>

      <template #step-3>
        <MapaForm
          v-model:geom="form.areaAfetada"
          :municipios="form.municipios"
        />
      </template>

      <template #step-4>
        <DadosHumanosForm
          v-model="form.danosHumanos"
          :municipios="form.municipios"
        />
      </template>

      <template #step-5>
        <DadosMateriaisForm
          v-model="form.danosMateriais"
          :municipios="form.municipios"
        />
      </template>

      <template #step-6>
        <ProcessoRevisao
          :form="form"
          @edit-step="goToStep"
        />
      </template>
    </ProcessoWizard>
  </ProcessoFormTemplate>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useProcessoForm } from '@/Composables/Decretacoes/useProcessoForm'

const steps = [
  { number: 1, label: 'Básico', icon: 'document-text' },
  { number: 2, label: 'Municípios', icon: 'map-pin' },
  { number: 3, label: 'Mapa', icon: 'map' },
  { number: 4, label: 'Danos H.', icon: 'users' },
  { number: 5, label: 'Danos M.', icon: 'home' },
  { number: 6, label: 'Revisão', icon: 'check-circle' }
]

const { form, currentStep, goToStep, submit } = useProcessoForm()

const handleSubmit = async () => {
  router.post(route('decretacoes.store'), form, {
    onSuccess: () => {
      // Redirecionar para visualização
    },
    onError: (errors) => {
      // Mostrar erros e voltar para step com erro
    }
  })
}
</script>
```

---

### 3.3 ProcessoShow.vue - Visualização Detalhada

#### Layout Proposto

```
┌─────────────────────────────────────────────────────────────┐
│  [🏠 Home] > [Decretações] > [Processo #12345]              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 🏷️ Processo Municipal #12345                          │  │
│  │ Enchente (COBRADE 1.2.1.1.0)                         │  │
│  │                                                        │  │
│  │ Status: 🟢 Reconhecido pelo Estado                    │  │
│  │ Prazo: ⚠️ 15 dias restantes                           │  │
│  │                                                        │  │
│  │ [✏️ Editar] [🖨️ Imprimir] [📤 Exportar] [🗑️ Excluir]   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌───── Timeline de Status ──────────────────────────────┐ │
│  │                                                        │ │
│  │  ● Registro          15/12/2024  João Silva           │ │
│  │  │                                                     │ │
│  │  ● Em Análise        16/12/2024  Sistema              │ │
│  │  │                                                     │ │
│  │  ● Reconhecido       20/12/2024  Maria Santos         │ │
│  │  │                                                     │ │
│  │  ○ Aguardando União  Pendente                         │ │
│  │                                                        │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                             │
│  ┌─ Tabs ───────────────────────────────────────────────┐ │
│  │ [Dados Gerais] [Municípios] [Danos] [Documentos] [Log] │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                             │
│  📋 Dados Gerais                                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Tipo Desastre:    Enchente (1.2.1.1.0)              │  │
│  │ Data Ocorrência:  14/12/2024                         │  │
│  │ Data Entrada:     15/12/2024                         │  │
│  │ Origem:           Municipal                          │  │
│  │ Protocolo FIDE:   12345-2024                         │  │
│  │ Decreto Municipal: 2024/001                          │  │
│  │ Data Decreto:     14/12/2024                         │  │
│  │ Data Publicação:  16/12/2024                         │  │
│  │ Prazo Vigência:   90 dias                            │  │
│  │ Data Vencimento:  16/03/2025                         │  │
│  │ Analista:         João Silva                         │  │
│  │ Processo SEI:     123456/2024                        │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  🗺️ Mapa da Área Afetada                                   │
│  [Mapa interativo com polígono]                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### Tab: Municípios

```
┌──────────────────────────────────────────────────────┐
│  📍 Municípios Afetados                              │
│                                                      │
│  ┌────────────────────────────────────────────────┐ │
│  │ 🏙️ Belo Horizonte                              │ │
│  │ ─────────────────────────────────────────────  │ │
│  │ População:        2.530.701                    │ │
│  │ Redec:            01                           │ │
│  │ Protocolo FIDE:   12345-2024                   │ │
│  │ Macrorregião:     Metropolitana de BH          │ │
│  │ Coordenadas:      -19.9166, -43.9344           │ │
│  │                                                │ │
│  │ Danos Específicos:                             │ │
│  │ • Desabrigados: 150                            │ │
│  │ • Desalojados: 300                             │ │
│  │ • Residências Destruídas: 45                   │ │
│  │ • Prejuízo Total: R$ 6.150.000,00              │ │
│  │                                                │ │
│  │ [📊 Ver Detalhes] [📍 Ver no Mapa]             │ │
│  └────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────┘
```

#### Tab: Danos

```
┌──────────────────────────────────────────────────────┐
│  📊 Resumo de Danos e Prejuízos                      │
│                                                      │
│  👥 Danos Humanos                                    │
│  ┌────────────────┬──────────┬──────────────────┐   │
│  │   Categoria    │ Quantidade│  % População    │   │
│  ├────────────────┼──────────┼──────────────────┤   │
│  │ Óbitos         │     0    │      0%         │   │
│  │ Feridos        │     5    │   0.0002%       │   │
│  │ Desabrigados   │   150    │   0.0059%       │   │
│  │ Desalojados    │   300    │   0.0118%       │   │
│  │ Desaparecidos  │     0    │      0%         │   │
│  │ Outros Afetados│   500    │   0.0197%       │   │
│  ├────────────────┼──────────┼──────────────────┤   │
│  │ TOTAL          │   955    │   0.0377%       │   │
│  └────────────────┴──────────┴──────────────────┘   │
│                                                      │
│  🏠 Danos Materiais                                  │
│  ┌────────────────┬──────────┬──────────┬──────────┐│
│  │     Item       │Destruídas│Danificadas│Valor(R$)││
│  ├────────────────┼──────────┼──────────┼──────────┤│
│  │ Residências    │    45    │   120    │1.500.000││
│  │ Comércios      │    12    │    35    │  800.000││
│  │ Escolas        │     0    │     2    │  150.000││
│  │ Postos Saúde   │     0    │     1    │  200.000││
│  ├────────────────┼──────────┼──────────┼──────────┤│
│  │ TOTAL          │    57    │   158    │2.650.000││
│  └────────────────┴──────────┴──────────┴──────────┘│
│                                                      │
│  💰 Prejuízos Econômicos                             │
│  ┌────────────────┬──────────────┬─────────────────┐│
│  │     Setor      │  Tipo        │   Valor (R$)   ││
│  ├────────────────┼──────────────┼─────────────────┤│
│  │ Agricultura    │  Público     │    2.000.000   ││
│  │ Indústria      │  Privado     │    1.000.000   ││
│  │ Comércio       │  Privado     │      500.000   ││
│  ├────────────────┴──────────────┼─────────────────┤│
│  │ TOTAL                         │    3.500.000   ││
│  └───────────────────────────────┴─────────────────┘│
│                                                      │
│  📈 Gráficos                                         │
│  [Gráfico de pizza: Danos Humanos por categoria]    │
│  [Gráfico de barras: Prejuízos por setor]           │
│                                                      │
│  [📥 Baixar Relatório PDF]                           │
└──────────────────────────────────────────────────────┘
```

---

## 4. Componentes Reutilizáveis

### 4.1 COBRADESelect.vue

```vue
<template>
  <Combobox v-model="selected">
    <div class="relative">
      <ComboboxInput
        class="w-full rounded-lg border border-gray-300 py-2 pl-3 pr-10"
        :display-value="(item) => item ? `${item.cobrade} - ${item.a_definicao}` : ''"
        placeholder="Buscar tipo de desastre..."
        @change="query = $event.target.value"
      />
      <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
        <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
      </ComboboxButton>

      <ComboboxOptions
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg"
      >
        <div v-if="filteredDesastres.length === 0" class="px-4 py-2 text-gray-700">
          Nenhum resultado encontrado.
        </div>

        <ComboboxOption
          v-for="desastre in filteredDesastres"
          :key="desastre.id"
          :value="desastre"
          v-slot="{ active, selected }"
        >
          <li
            :class="[
              'relative cursor-default select-none py-2 pl-10 pr-4',
              active ? 'bg-primary-600 text-white' : 'text-gray-900'
            ]"
          >
            <span :class="['block truncate', selected && 'font-medium']">
              {{ desastre.cobrade }} - {{ desastre.a_definicao }}
            </span>
            <span
              v-if="selected"
              :class="[
                'absolute inset-y-0 left-0 flex items-center pl-3',
                active ? 'text-white' : 'text-primary-600'
              ]"
            >
              <CheckIcon class="h-5 w-5" />
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </div>
  </Combobox>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  modelValue: [Number, String],
  desastres: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const selected = computed({
  get: () => props.desastres.find(d => d.id === props.modelValue),
  set: (value) => emit('update:modelValue', value?.id)
})

const filteredDesastres = computed(() => {
  if (query.value === '') return props.desastres

  return props.desastres.filter((desastre) => {
    return (
      desastre.cobrade?.toLowerCase().includes(query.value.toLowerCase()) ||
      desastre.a_definicao?.toLowerCase().includes(query.value.toLowerCase()) ||
      desastre.tipo?.toLowerCase().includes(query.value.toLowerCase())
    )
  })
})
</script>
```

### 4.2 StatusTimeline.vue

```vue
<template>
  <div class="flow-root">
    <ul role="list" class="-mb-8">
      <li v-for="(event, idx) in timeline" :key="event.id">
        <div class="relative pb-8">
          <span
            v-if="idx !== timeline.length - 1"
            class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"
            aria-hidden="true"
          />
          <div class="relative flex space-x-3">
            <div>
              <span
                :class="[
                  event.completed ? 'bg-green-500' : 'bg-gray-300',
                  'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white'
                ]"
              >
                <component
                  :is="event.icon"
                  class="h-5 w-5 text-white"
                  aria-hidden="true"
                />
              </span>
            </div>
            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
              <div>
                <p class="text-sm font-medium text-gray-900">
                  {{ event.label }}
                </p>
                <p v-if="event.user" class="text-sm text-gray-500">
                  {{ event.user }}
                </p>
              </div>
              <div class="whitespace-nowrap text-right text-sm text-gray-500">
                <time :datetime="event.datetime">{{ event.date }}</time>
              </div>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  DocumentTextIcon,
  EyeIcon,
  CheckCircleIcon,
  ClockIcon
} from '@heroicons/vue/24/solid'

const props = defineProps({
  processo: {
    type: Object,
    required: true
  }
})

const statusToTimeline = {
  'Registro': { icon: DocumentTextIcon, order: 1 },
  'Aguardando Análise do Estado': { icon: ClockIcon, order: 2 },
  'Em análise pelo Estado': { icon: EyeIcon, order: 3 },
  'Reconhecido pelo Estado': { icon: CheckCircleIcon, order: 4 },
  'Reconhecido pelo Estado e pela União': { icon: CheckCircleIcon, order: 5 }
}

const timeline = computed(() => {
  const currentStatus = props.processo.reconhecimento
  const currentOrder = statusToTimeline[currentStatus]?.order || 0

  return Object.entries(statusToTimeline).map(([status, config]) => ({
    id: status,
    label: status,
    icon: config.icon,
    completed: config.order <= currentOrder,
    date: config.order <= currentOrder ? props.processo.updated_at : null,
    user: config.order <= currentOrder ? props.processo.analista : null
  }))
})
</script>
```

### 4.3 ProcessoCard.vue

```vue
<template>
  <div
    class="rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow p-6"
  >
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <div class="flex items-center gap-2">
          <StatusBadge :status="processo.reconhecimento" />
          <TipoDesastreBadge :cobrade="processo.tipo_desastre_cobrade" />
        </div>
        <h3 class="mt-2 text-lg font-semibold text-gray-900">
          {{ processo.tipo_desastre_nome }}
        </h3>
      </div>
      <PrazoBadge
        :dias-restantes="processo.dias_restantes"
        :vencido="processo.dias_restantes === 0"
      />
    </div>

    <!-- Info -->
    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
      <div>
        <dt class="font-medium text-gray-500">Protocolo</dt>
        <dd class="mt-1 text-gray-900">{{ processo.n_protocolo_fide }}</dd>
      </div>
      <div>
        <dt class="font-medium text-gray-500">Data</dt>
        <dd class="mt-1 text-gray-900">{{ formatDate(processo.data_entrada) }}</dd>
      </div>
      <div v-if="processo.processo === 'ESTADUAL'" class="col-span-2">
        <dt class="font-medium text-gray-500">Municípios</dt>
        <dd class="mt-1 text-gray-900">
          {{ processo.municipios.map(m => m.p_nome).join(', ') }}
        </dd>
      </div>
    </dl>

    <!-- Totais -->
    <div v-if="processo.desastre_totals" class="mt-4 flex gap-4 text-sm">
      <div class="flex items-center gap-1">
        <UsersIcon class="h-4 w-4 text-gray-400" />
        <span class="text-gray-900">
          {{ getTotalAfetados(processo) }} afetados
        </span>
      </div>
      <div class="flex items-center gap-1">
        <HomeIcon class="h-4 w-4 text-gray-400" />
        <span class="text-gray-900">
          {{ getTotalCasas(processo) }} casas
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 flex justify-end gap-2">
      <Button
        @click="$emit('view', processo)"
        variant="outline"
        size="sm"
      >
        <EyeIcon class="h-4 w-4" />
        Ver
      </Button>
      <Button
        @click="$emit('edit', processo)"
        variant="primary"
        size="sm"
      >
        <PencilIcon class="h-4 w-4" />
        Editar
      </Button>
    </div>
  </div>
</template>

<script setup>
import { UsersIcon, HomeIcon, EyeIcon, PencilIcon } from '@heroicons/vue/24/outline'

defineProps({
  processo: {
    type: Object,
    required: true
  }
})

defineEmits(['view', 'edit'])

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR')
}

const getTotalAfetados = (processo) => {
  // Lógica para somar afetados dos totais
  return 0
}

const getTotalCasas = (processo) => {
  // Lógica para somar casas dos totais
  return 0
}
</script>
```

---

## 5. Composables

### 5.1 useProcessoForm.js

```javascript
import { ref, reactive, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

export function useProcessoForm(processo = null) {
  const { toast } = useToast()
  const currentStep = ref(1)
  const isDirty = ref(false)

  const form = reactive({
    dadosBasicos: {
      tipo_desastre_id: processo?.tipo_desastre_id || null,
      data_entrada: processo?.data_entrada || null,
      data_ocorrencia_desastre: processo?.data_ocorrencia_desastre || null,
      processo: processo?.processo || 'MUNICIPAL',
      reconhecimento: processo?.reconhecimento || 'Registro',
      analista: processo?.analista || null,
      n_protocolo_fide: processo?.n_protocolo_fide || null,
      decreto_municipal: processo?.decreto_municipal || null,
      data_decreto_municipal: processo?.data_decreto_municipal || null,
      data_publicacao_mg: processo?.data_publicacao_mg || null,
      prazo_vigencia: processo?.prazo_vigencia || 90,
      observacoes: processo?.observacoes || null
    },
    municipios: processo?.municipios || [],
    areaAfetada: processo?.area_afetada || null,
    danosHumanos: processo?.danos_humanos || {},
    danosMateriais: processo?.danos_materiais || {},
    prejuizos: processo?.prejuizos || []
  })

  const totalSteps = 6

  const canGoNext = computed(() => {
    switch (currentStep.value) {
      case 1:
        return form.dadosBasicos.tipo_desastre_id &&
               form.dadosBasicos.processo &&
               form.dadosBasicos.reconhecimento
      case 2:
        return form.municipios.length > 0
      case 3:
        return true // Mapa é opcional
      case 4:
      case 5:
        return true // Danos são opcionais no wizard
      default:
        return true
    }
  })

  const goNext = () => {
    if (currentStep.value < totalSteps && canGoNext.value) {
      currentStep.value++
      saveDraft()
    }
  }

  const goBack = () => {
    if (currentStep.value > 1) {
      currentStep.value--
    }
  }

  const goToStep = (step) => {
    if (step >= 1 && step <= totalSteps) {
      currentStep.value = step
    }
  }

  const saveDraft = async () => {
    try {
      await axios.post('/api/decretacoes/draft', form)
      isDirty.value = false
    } catch (error) {
      console.error('Erro ao salvar rascunho', error)
    }
  }

  const submit = () => {
    return form
  }

  return {
    form,
    currentStep,
    totalSteps,
    canGoNext,
    isDirty,
    goNext,
    goBack,
    goToStep,
    saveDraft,
    submit
  }
}
```

### 5.2 useProcessoFilters.js

```javascript
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

export function useProcessoFilters(initialFilters = {}) {
  const filters = reactive({
    search: initialFilters.search || '',
    data_entrada_inicio: initialFilters.data_entrada_inicio || null,
    data_entrada_fim: initialFilters.data_entrada_fim || null,
    processo: initialFilters.processo || null,
    reconhecimento: initialFilters.reconhecimento || [],
    analista: initialFilters.analista || null,
    tipo_desastre_id: initialFilters.tipo_desastre_id || null,
    municipio_id: initialFilters.municipio_id || null,
    vigencia_status: initialFilters.vigencia_status || 'todos',
    n_protocolo_fide: initialFilters.n_protocolo_fide || ''
  })

  const activeFiltersCount = computed(() => {
    return Object.values(filters).filter(v => {
      if (Array.isArray(v)) return v.length > 0
      if (v === null || v === '' || v === 'todos') return false
      return true
    }).length
  })

  const handleFilterChange = debounce(() => {
    router.get(
      route('decretacoes.index'),
      filters,
      { preserveState: true, preserveScroll: true }
    )
  }, 300)

  const clearFilters = () => {
    Object.keys(filters).forEach(key => {
      if (Array.isArray(filters[key])) {
        filters[key] = []
      } else {
        filters[key] = key === 'vigencia_status' ? 'todos' : null
      }
    })
    handleFilterChange()
  }

  watch(
    filters,
    () => handleFilterChange(),
    { deep: true }
  )

  return {
    filters,
    activeFiltersCount,
    handleFilterChange,
    clearFilters
  }
}
```

---

## 6. Responsividade Mobile

### 6.1 Breakpoints

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'sm': '640px',
      'md': '768px',
      'lg': '1024px',
      'xl': '1280px',
      '2xl': '1536px'
    }
  }
}
```

### 6.2 Adaptações Mobile

#### Index (Mobile)
- Cards em coluna única
- Filtros em bottom sheet/drawer
- Ações em menu dropdown
- Stats em carousel horizontal

#### Wizard (Mobile)
- Steps em stepper compacto
- Navegação com botões fixos no rodapé
- Campos full-width
- Inputs maiores para touch

#### Show (Mobile)
- Tabs em pills horizontais com scroll
- Mapa full-screen com botão
- Cards colapsáveis
- Timeline vertical compacta

---

## 7. Acessibilidade

### 7.1 Checklist WCAG 2.1

- [ ] Contraste mínimo 4.5:1 para textos
- [ ] Contraste mínimo 3:1 para componentes UI
- [ ] Navegação por teclado em todos os elementos
- [ ] ARIA labels em ícones
- [ ] Roles semânticos
- [ ] Skip links
- [ ] Focus visível
- [ ] Textos alternativos em imagens
- [ ] Mensagens de erro claras
- [ ] Zoom até 200% sem quebra

### 7.2 Exemplo de Implementação

```vue
<template>
  <button
    type="button"
    class="btn btn-primary"
    aria-label="Criar novo processo de reconhecimento"
    :aria-disabled="loading"
    @click="handleCreate"
  >
    <PlusIcon aria-hidden="true" />
    <span>Novo Processo</span>
  </button>
</template>
```

---

## 8. Performance

### 8.1 Otimizações

1. **Lazy Loading de Componentes**
```javascript
const ProcessoShow = defineAsyncComponent(() =>
  import('@/Pages/Decretacoes/ProcessoShow.vue')
)
```

2. **Virtual Scrolling para Listas Grandes**
```vue
<VirtualScroller
  :items="processos"
  :item-height="120"
  key-field="id"
>
  <template #default="{ item }">
    <ProcessoCard :processo="item" />
  </template>
</VirtualScroller>
```

3. **Debounce em Searches**
```javascript
const searchDebounced = debounce((value) => {
  filters.search = value
}, 300)
```

4. **Image Optimization**
```vue
<img
  :src="municipio.brasao"
  loading="lazy"
  decoding="async"
  width="40"
  height="40"
/>
```

---

## 9. Testes

### 9.1 Testes de Componente

```javascript
import { mount } from '@vue/test-utils'
import ProcessoCard from '@/Components/Organisms/Decretacoes/ProcessoCard.vue'

describe('ProcessoCard', () => {
  it('exibe informações do processo corretamente', () => {
    const processo = {
      id: 1,
      tipo_desastre_nome: 'Enchente',
      n_protocolo_fide: '12345',
      reconhecimento: 'Reconhecido'
    }

    const wrapper = mount(ProcessoCard, {
      props: { processo }
    })

    expect(wrapper.text()).toContain('Enchente')
    expect(wrapper.text()).toContain('12345')
    expect(wrapper.find('[data-test="status-badge"]').exists()).toBe(true)
  })

  it('emite evento ao clicar em ver', async () => {
    const wrapper = mount(ProcessoCard, {
      props: { processo: { id: 1 } }
    })

    await wrapper.find('[data-test="btn-view"]').trigger('click')

    expect(wrapper.emitted('view')).toBeTruthy()
  })
})
```

### 9.2 Testes E2E (Cypress)

```javascript
describe('Criação de Processo', () => {
  it('cria processo municipal com sucesso', () => {
    cy.visit('/decretacoes/create')

    // Step 1
    cy.get('[data-test="tipo-desastre-select"]').select('Enchente')
    cy.get('[data-test="processo-municipal"]').check()
    cy.get('[data-test="btn-next"]').click()

    // Step 2
    cy.get('[data-test="municipio-select"]').select('Belo Horizonte')
    cy.get('[data-test="btn-next"]').click()

    // Step 3 (pular mapa)
    cy.get('[data-test="btn-next"]').click()

    // Step 4
    cy.get('[data-test="danos-desabrigados"]').type('150')
    cy.get('[data-test="btn-next"]').click()

    // Step 5
    cy.get('[data-test="danos-casas-destruidas"]').type('45')
    cy.get('[data-test="btn-next"]').click()

    // Step 6
    cy.get('[data-test="btn-submit"]').click()

    cy.url().should('include', '/decretacoes/')
    cy.contains('Processo criado com sucesso')
  })
})
```

---

## 10. Documentação de Componentes (Storybook)

```javascript
// ProcessoCard.stories.js
import ProcessoCard from '@/Components/Organisms/Decretacoes/ProcessoCard.vue'

export default {
  title: 'Organisms/Decretacoes/ProcessoCard',
  component: ProcessoCard,
  argTypes: {
    processo: { control: 'object' }
  }
}

const Template = (args) => ({
  components: { ProcessoCard },
  setup() {
    return { args }
  },
  template: '<ProcessoCard v-bind="args" />'
})

export const Default = Template.bind({})
Default.args = {
  processo: {
    id: 1,
    tipo_desastre_nome: 'Enchente',
    tipo_desastre_cobrade: '1.2.1.1.0',
    n_protocolo_fide: '12345-2024',
    reconhecimento: 'Reconhecido pelo Estado',
    dias_restantes: 45,
    municipios: [{ p_nome: 'Belo Horizonte' }]
  }
}

export const ProximoVencer = Template.bind({})
ProximoVencer.args = {
  ...Default.args,
  processo: {
    ...Default.args.processo,
    dias_restantes: 10
  }
}

export const Vencido = Template.bind({})
Vencido.args = {
  ...Default.args,
  processo: {
    ...Default.args.processo,
    dias_restantes: 0
  }
}
```

---

**Documento criado em**: 2025-12-27
**Versão**: 1.0
**Framework**: Vue 3 + Inertia.js + TailwindCSS
