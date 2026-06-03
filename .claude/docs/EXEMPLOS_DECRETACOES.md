---
# EXEMPLOS PRÁTICOS - Defesa Civil DECRETACOES Module
## Implementação Real Baseada no Padrão
---

## Exemplo 1: ProcessoCard.vue (Organism)

```vue
<template>
  <article 
    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow"
    :aria-label="`Processo ${processo.n_protocolo_fide}`"
  >
    <!-- Header: Tipo e Badge COBRADE -->
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 px-lg py-md border-b border-gray-200">
      <div class="flex items-start justify-between mb-sm">
        <div class="flex items-center gap-md">
          <!-- Tipo Processo Badge -->
          <span
            :class="[
              'inline-flex items-center px-lg py-xs rounded-full text-xs font-bold',
              processo.tipo_processo === 'ESTADUAL'
                ? 'bg-blue-100 text-blue-800'
                : 'bg-purple-100 text-purple-800'
            ]"
          >
            🏷️ {{ processo.tipo_processo }}
          </span>

          <!-- Tipo Desastre Badge -->
          <span
            class="inline-flex items-center px-md py-xs rounded bg-gray-100 text-gray-700 text-xs font-medium"
            :title="processo.tipo_desastre_cobrade"
          >
            ⓘ {{ processo.tipo_desastre_nome }}
          </span>
        </div>

        <!-- Municípios Count -->
        <span class="text-sm font-semibold text-gray-700">
          📍 {{ processo.municipios.length }} municípios
        </span>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-lg py-md space-y-md">
      <!-- Protocolo e Status -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-xs">
            Protocolo FIDE
          </label>
          <code class="text-lg font-mono font-bold text-gray-900">
            {{ processo.n_protocolo_fide }}
          </code>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-xs">
            Status Reconhecimento
          </label>
          <StatusBadge :status="processo.reconhecimento" />
        </div>
      </div>

      <!-- Data Entrada e Prazo -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-xs">
            Data de Entrada
          </label>
          <time class="text-sm font-medium text-gray-900">
            {{ formatDate(processo.data_entrada) }}
          </time>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-xs">
            Prazo Restante
          </label>
          <PrazoBadge :dias="processo.dias_restantes" />
        </div>
      </div>

      <!-- COBRADE Info -->
      <div class="bg-amber-50 rounded-lg border border-amber-200 p-md">
        <p class="text-xs font-semibold text-amber-900 mb-xs">
          Código COBRADE: <code>{{ processo.tipo_desastre_cobrade }}</code>
        </p>
        <p class="text-sm text-amber-800">
          {{ processo.tipo_desastre_nome }}
        </p>
      </div>

      <!-- Danos Resumidos -->
      <div class="bg-red-50 rounded-lg p-md">
        <h4 class="text-xs font-bold text-red-900 mb-md">Danos Registrados</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-md text-center">
          <div>
            <p class="text-2xl font-bold text-red-700">{{ processo.danos_desabrigados }}</p>
            <p class="text-xs text-red-600">Desabrigados</p>
          </div>
          <div>
            <p class="text-2xl font-bold text-red-700">{{ processo.danos_feridos }}</p>
            <p class="text-xs text-red-600">Feridos</p>
          </div>
          <div>
            <p class="text-2xl font-bold text-red-700">{{ processo.danos_mortos }}</p>
            <p class="text-xs text-red-600">Óbitos</p>
          </div>
        </div>
      </div>

      <!-- Municípios Afetados -->
      <div>
        <h4 class="text-xs font-bold text-gray-900 mb-sm">Municípios Afetados</h4>
        <div class="flex flex-wrap gap-xs">
          <span 
            v-for="mun in processo.municipios.slice(0, 3)"
            :key="mun.id"
            class="inline-flex items-center px-md py-xs bg-gray-100 text-gray-700 rounded-full text-xs font-medium"
          >
            📍 {{ mun.p_nome }}
          </span>
          <span 
            v-if="processo.municipios.length > 3"
            class="inline-flex items-center px-md py-xs bg-gray-100 text-gray-700 rounded-full text-xs font-medium"
          >
            +{{ processo.municipios.length - 3 }} outros
          </span>
        </div>
      </div>
    </div>

    <!-- Actions Footer -->
    <div class="flex gap-sm p-md bg-gray-50 border-t border-gray-200 rounded-b-lg">
      <Button
        variant="ghost"
        size="sm"
        class="flex-1"
        @click="$emit('view')"
      >
        👁️ Ver Detalhes
      </Button>
      <Button
        variant="primary"
        size="sm"
        class="flex-1"
        @click="$emit('edit')"
      >
        ✏️ Editar
      </Button>
    </div>
  </article>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import StatusBadge from '@/Molecules/Decretacoes/Badges/StatusBadge.vue'
import PrazoBadge from '@/Molecules/Decretacoes/Badges/PrazoBadge.vue'
import Button from '@/Atoms/Decretacoes/Button.vue'

interface Municipio {
  id: number
  p_nome: string
}

interface Processo {
  id: number
  tipo_processo: 'ESTADUAL' | 'MUNICIPAL'
  tipo_desastre_nome: string
  tipo_desastre_cobrade: string
  n_protocolo_fide: string
  reconhecimento: 'Reconhecido' | 'Não Reconhecido' | 'Em Análise'
  data_entrada: string
  dias_restantes: number
  municipios: Municipio[]
  danos_desabrigados: number
  danos_feridos: number
  danos_mortos: number
}

const props = defineProps<{
  processo: Processo
}>()

defineEmits<{
  view: []
  edit: []
}>()

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}
</script>
```

---

## Exemplo 2: StatusBadge.vue (Molecule)

```vue
<template>
  <span
    :class="[
      'inline-flex items-center gap-xs px-md py-sm rounded-full text-sm font-semibold whitespace-nowrap',
      statusStyles.bg,
      statusStyles.text
    ]"
    :aria-label="`Status: ${statusLabel}`"
  >
    <span 
      :class="[
        'inline-block w-2.5 h-2.5 rounded-full',
        statusStyles.dot
      ]"
      aria-hidden="true"
    />
    {{ statusLabel }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type Status = 'Reconhecido' | 'Não Reconhecido' | 'Em Análise' | 'Revogado'

interface Props {
  status: Status
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md'
})

const statusConfig: Record<Status, { bg: string; text: string; dot: string; label: string }> = {
  'Reconhecido': {
    bg: 'bg-green-50',
    text: 'text-green-700',
    dot: 'bg-green-500',
    label: '✓ Reconhecido'
  },
  'Não Reconhecido': {
    bg: 'bg-gray-50',
    text: 'text-gray-700',
    dot: 'bg-gray-400',
    label: '✗ Não Reconhecido'
  },
  'Em Análise': {
    bg: 'bg-blue-50',
    text: 'text-blue-700',
    dot: 'bg-blue-500',
    label: '⏳ Em Análise'
  },
  'Revogado': {
    bg: 'bg-red-50',
    text: 'text-red-700',
    dot: 'bg-red-500',
    label: '⛔ Revogado'
  }
}

const statusStyles = computed(() => statusConfig[props.status])

const statusLabel = computed(() => statusConfig[props.status].label)
</script>
```

---

## Exemplo 3: PrazoBadge.vue (Molecule)

```vue
<template>
  <span
    :class="[
      'inline-flex items-center gap-xs px-md py-sm rounded-full text-sm font-bold whitespace-nowrap',
      prazoStyles.bg,
      prazoStyles.text
    ]"
    :aria-label="`${prazoLabel}`"
  >
    <span :aria-hidden="true">{{ prazoStyles.icon }}</span>
    {{ prazoLabel }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  dias: number
}

const props = defineProps<Props>()

const prazoStyles = computed(() => {
  if (props.dias <= 0) {
    return {
      bg: 'bg-red-50',
      text: 'text-red-700',
      icon: '⏰',
      label: 'Vencido'
    }
  } else if (props.dias <= 15) {
    return {
      bg: 'bg-amber-50',
      text: 'text-amber-700',
      icon: '⚠️',
      label: `${props.dias} dias`
    }
  } else {
    return {
      bg: 'bg-green-50',
      text: 'text-green-700',
      icon: '✓',
      label: `${props.dias} dias`
    }
  }
})

const prazoLabel = computed(() => prazoStyles.value.label)
</script>
```

---

## Exemplo 4: ProcessoWizard.vue (Organism - Progressive Disclosure)

```vue
<template>
  <div class="space-y-lg">
    <!-- Progress Indicator com steps -->
    <div class="bg-white rounded-lg border border-gray-200 p-lg">
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, idx) in WIZARD_STEPS"
          :key="idx"
          class="flex-1 flex flex-col items-center relative"
        >
          <!-- Step circle -->
          <div
            :class="[
              'w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm transition-all',
              idx < currentStep 
                ? 'bg-green-500 text-white'
                : idx === currentStep
                ? 'bg-blue-500 text-white ring-4 ring-blue-200'
                : 'bg-gray-200 text-gray-600'
            ]"
          >
            {{ idx + 1 }}
          </div>

          <!-- Step label -->
          <p 
            class="mt-md text-xs font-semibold text-center text-gray-700 min-w-fit px-sm"
          >
            {{ step.label }}
          </p>

          <!-- Connector line -->
          <div 
            v-if="idx < WIZARD_STEPS.length - 1"
            :class="[
              'absolute top-6 left-1/2 w-full h-1 -z-10',
              idx < currentStep ? 'bg-green-500' : 'bg-gray-200'
            ]"
            style="left: calc(50% + 24px); width: calc(100% - 48px)"
          />
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-lg min-h-96">
      <h2 class="text-2xl font-bold text-gray-900 mb-lg">
        📍 {{ WIZARD_STEPS[currentStep].label }}
      </h2>

      <component
        :is="WIZARD_STEPS[currentStep].component"
        v-model="formData"
        :errors="errors"
      />
    </div>

    <!-- Navigation Buttons -->
    <div class="flex gap-md justify-between">
      <Button
        variant="outline"
        :disabled="currentStep === 0"
        @click="goToPrevious"
      >
        ← Anterior
      </Button>

      <div class="flex gap-md">
        <!-- Save Draft for later steps -->
        <Button
          v-if="currentStep > 0"
          variant="ghost"
          @click="saveDraft"
          :loading="isSavingDraft"
        >
          💾 Salvar Rascunho
        </Button>

        <!-- Next or Submit -->
        <Button
          :variant="currentStep === WIZARD_STEPS.length - 1 ? 'primary' : 'primary'"
          :loading="isLoading"
          @click="currentStep === WIZARD_STEPS.length - 1 ? submit() : goToNext()"
        >
          {{ currentStep === WIZARD_STEPS.length - 1 ? '✓ Criar Processo' : 'Próximo →' }}
        </Button>
      </div>
    </div>

    <!-- Error Messages -->
    <Transition>
      <div 
        v-if="globalError"
        role="alert"
        aria-live="polite"
        class="bg-red-50 border border-red-200 rounded-lg p-lg text-red-700"
      >
        ⚠️ {{ globalError }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import Button from '@/Atoms/Decretacoes/Button.vue'
import DadosBasicosForm from './Steps/DadosBasicosForm.vue'
import MunicipiosForm from './Steps/MunicipiosForm.vue'
import MapaForm from './Steps/MapaForm.vue'
import DadosHumanosForm from './Steps/DadosHumanosForm.vue'
import DadosMateriaisForm from './Steps/DadosMateriaisForm.vue'
import RevisaoForm from './Steps/RevisaoForm.vue'

const WIZARD_STEPS = [
  { id: 'basico', label: 'Dados Básicos', component: DadosBasicosForm },
  { id: 'municipios', label: 'Municípios', component: MunicipiosForm },
  { id: 'mapa', label: 'Mapa da Área', component: MapaForm },
  { id: 'danos-humanos', label: 'Danos Humanos', component: DadosHumanosForm },
  { id: 'danos-materiais', label: 'Danos Materiais', component: DadosMateriaisForm },
  { id: 'revisao', label: 'Revisão', component: RevisaoForm },
]

const currentStep = ref(0)
const isLoading = ref(false)
const isSavingDraft = ref(false)
const globalError = ref('')
const formData = reactive({
  tipo_processo: null,
  tipo_desastre_id: null,
  data_entrada: new Date().toISOString().split('T')[0],
  data_ocorrencia: null,
  n_protocolo_fide: '',
  municipios: [],
  area_desastre: null,
  danos_desabrigados: 0,
  danos_feridos: 0,
  danos_mortos: 0,
  danos_casas_destruidas: 0,
  danos_casas_danificadas: 0,
  prejuizo_estimado: 0,
})
const errors = ref({})

const goToNext = async () => {
  // Validar step atual
  try {
    const stepId = WIZARD_STEPS[currentStep.value].id
    const validation = await validateStep(stepId, formData)
    
    if (validation.valid) {
      // Auto-save do passo
      await autosave()
      currentStep.value++
      globalError.value = ''
    } else {
      errors.value = validation.errors || {}
      globalError.value = 'Por favor, preencha os campos obrigatórios'
    }
  } catch (err) {
    globalError.value = 'Erro ao validar. Tente novamente.'
  }
}

const goToPrevious = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const saveDraft = async () => {
  isSavingDraft.value = true
  try {
    await fetch('/api/decretacoes/draft', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    })
  } catch (err) {
    globalError.value = 'Erro ao salvar rascunho'
  } finally {
    isSavingDraft.value = false
  }
}

const autosave = debounce(async () => {
  await fetch('/api/decretacoes/draft', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
  })
}, 5000)

const submit = async () => {
  isLoading.value = true
  try {
    const response = await fetch('/api/decretacoes', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    })
    
    if (response.ok) {
      const data = await response.json()
      router.visit(route('decretacoes.show', data.id))
    } else {
      const errData = await response.json()
      errors.value = errData.errors || {}
      globalError.value = 'Erro ao criar processo'
    }
  } catch (err) {
    globalError.value = 'Erro na submissão'
  } finally {
    isLoading.value = false
  }
}

const validateStep = async (stepId: string, data: any) => {
  // Backend validation
  const response = await fetch(`/api/decretacoes/validate/${stepId}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  return response.json()
}
</script>
```

---

## Exemplo 5: useProcessoFilters.ts (Composable)

```typescript
import { reactive, computed, watch } from 'vue'
import { debounce } from 'lodash'
import { router } from '@inertiajs/vue3'

export function useProcessoFilters(initialFilters: any = {}) {
  const filters = reactive({
    // Busca geral
    search: initialFilters.search || '',
    
    // Filtro por tipo
    processo: initialFilters.processo || null,              // ESTADUAL/MUNICIPAL
    tipo_desastre_id: initialFilters.tipo_desastre_id || null,
    
    // Filtro por período
    data_entrada_inicio: initialFilters.data_entrada_inicio || null,
    data_entrada_fim: initialFilters.data_entrada_fim || null,
    
    // Filtro por status
    reconhecimento: initialFilters.reconhecimento || [],
    vigencia_status: initialFilters.vigencia_status || 'todos', // todos/vigente/vencido/proximo
    
    // Filtro por localização
    municipio_id: initialFilters.municipio_id || null,
    
    // Filtro por analista
    analista_id: initialFilters.analista_id || null,
    
    // Filtro por protocolo
    n_protocolo_fide: initialFilters.n_protocolo_fide || '',
  })

  // Contar filtros ativos
  const activeFiltersCount = computed(() => {
    return Object.values(filters).filter(v => {
      if (Array.isArray(v)) return v.length > 0
      if (v === null || v === '' || v === 'todos') return false
      return true
    }).length
  })

  // Executar filter com debounce
  const applyFilters = debounce(() => {
    router.get(
      route('decretacoes.index'),
      filters,
      {
        preserveState: true,
        preserveScroll: true,
      }
    )
  }, 300)

  // Limpar todos os filtros
  const clearFilters = () => {
    Object.keys(filters).forEach(key => {
      if (Array.isArray(filters[key])) {
        filters[key] = []
      } else if (key === 'vigencia_status') {
        filters[key] = 'todos'
      } else {
        filters[key] = null
      }
    })
  }

  // Quick filter via stat cards
  const applyQuickFilter = (type: 'vigente' | 'vencido' | 'proximo') => {
    filters.vigencia_status = type === 'proximo' ? 'proximo_vencer' : type
    applyFilters()
  }

  // Watch todos os filtros
  watch(
    filters,
    () => applyFilters(),
    { deep: true }
  )

  return {
    filters,
    activeFiltersCount,
    applyFilters,
    clearFilters,
    applyQuickFilter
  }
}
```

---

## Exemplo 6: ProcessoIndex.vue (Página Completa)

```vue
<template>
  <ProcessoIndexTemplate>
    <!-- Header Section -->
    <template #header>
      <div class="flex items-center justify-between mb-lg">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            📋 Reconhecimentos de Desastre
          </h1>
          <p class="text-gray-600 mt-sm">
            Gestão centralizada de processos de reconhecimento de desastres naturais
          </p>
        </div>
        <Button
          v-if="canCreate"
          variant="primary"
          size="lg"
          @click="router.visit(route('decretacoes.create'))"
        >
          <PlusIcon />
          Novo Processo
        </Button>
      </div>
    </template>

    <!-- Stats Section -->
    <template #stats>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
        <StatCard
          title="Total de Processos"
          :value="stats.total"
          icon="📊"
          @click="filters.vigencia_status = 'todos'"
        />
        <StatCard
          title="Vigentes"
          :value="stats.vigentes"
          variant="success"
          icon="✓"
          @click="filters.vigencia_status = 'vigente'"
        />
        <StatCard
          title="Vencidos"
          :value="stats.vencidos"
          variant="danger"
          icon="⏰"
          @click="filters.vigencia_status = 'vencido'"
        />
        <StatCard
          title="Próximos ao Vencer"
          :value="stats.proximo_vencer"
          variant="warning"
          icon="⚠️"
          @click="filters.vigencia_status = 'proximo_vencer'"
        />
      </div>
    </template>

    <!-- Filters Section -->
    <template #filters>
      <div class="bg-white rounded-lg border border-gray-200 p-lg space-y-md">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Filtros</h3>
          <span v-if="activeFiltersCount > 0" class="text-xs font-bold bg-blue-100 text-blue-700 px-md py-xs rounded-full">
            {{ activeFiltersCount }} ativo
          </span>
        </div>

        <!-- Quick Search -->
        <Input
          v-model="filters.search"
          placeholder="🔍 Buscar por protocolo, município, tipo..."
          type="text"
        />

        <!-- Advanced Filters -->
        <details class="border-t pt-md">
          <summary class="font-semibold text-gray-900 cursor-pointer">
            Filtros Avançados
          </summary>

          <div class="mt-md space-y-md">
            <!-- Tipo Processo -->
            <FilterGroup label="Tipo de Processo">
              <RadioGroup
                v-model="filters.processo"
                :options="[
                  { value: 'ESTADUAL', label: 'Estadual' },
                  { value: 'MUNICIPAL', label: 'Municipal' }
                ]"
              />
            </FilterGroup>

            <!-- COBRADE Type -->
            <FilterGroup label="Tipo de Desastre">
              <COBRADESelect
                v-model="filters.tipo_desastre_id"
                placeholder="Selecione..."
              />
            </FilterGroup>

            <!-- Date Range -->
            <FilterGroup label="Período">
              <DateRangePicker
                v-model:start="filters.data_entrada_inicio"
                v-model:end="filters.data_entrada_fim"
              />
            </FilterGroup>

            <!-- Vigência Status -->
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
          </div>
        </details>

        <!-- Filter Actions -->
        <div class="flex gap-sm pt-md border-t">
          <Button
            variant="primary"
            full
            @click="applyFilters"
          >
            Aplicar Filtros
          </Button>
          <Button
            v-if="activeFiltersCount > 0"
            variant="outline"
            full
            @click="clearFilters"
          >
            Limpar
          </Button>
        </div>
      </div>
    </template>

    <!-- Content Section (Cards Grid) -->
    <template #content>
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-2xl">
        <p class="text-gray-600">Carregando processos...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="processos.data.length === 0" class="text-center py-2xl">
        <p class="text-2xl mb-md">📭</p>
        <p class="text-gray-600">Nenhum processo encontrado com os filtros selecionados</p>
      </div>

      <!-- Cards Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
        <ProcessoCard
          v-for="processo in processos.data"
          :key="processo.id"
          :processo="processo"
          @view="viewProcesso(processo)"
          @edit="editProcesso(processo)"
        />
      </div>

      <!-- Pagination -->
      <div v-if="processos.meta" class="mt-xl flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Mostrando {{ processos.from }} a {{ processos.to }} de {{ processos.total }}
        </p>

        <div class="flex gap-sm">
          <Button
            variant="outline"
            size="sm"
            :disabled="!processos.prev_page_url"
            @click="router.visit(processos.prev_page_url)"
          >
            ← Anterior
          </Button>

          <div class="flex gap-xs">
            <Button
              v-for="link in processos.links.slice(1, -1)"
              :key="link.label"
              :variant="link.active ? 'primary' : 'outline'"
              size="sm"
              @click="router.visit(link.url)"
              :disabled="!link.url"
            >
              {{ link.label }}
            </Button>
          </div>

          <Button
            variant="outline"
            size="sm"
            :disabled="!processos.next_page_url"
            @click="router.visit(processos.next_page_url)"
          >
            Próxima →
          </Button>
        </div>
      </div>
    </template>
  </ProcessoIndexTemplate>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useProcessoFilters } from '@/Composables/Decretacoes/useProcessoFilters'
import { useAcl } from '@/Composables/useAcl'
import ProcessoIndexTemplate from '@/Templates/Decretacoes/ProcessoIndexTemplate.vue'
import ProcessoCard from '@/Organisms/Decretacoes/ProcessoCard/ProcessoCard.vue'
import StatCard from '@/Molecules/Decretacoes/Cards/StatCard.vue'
import COBRADESelect from '@/Molecules/Decretacoes/Inputs/COBRADESelect.vue'
import DateRangePicker from '@/Molecules/Decretacoes/Compostos/DateRangePicker.vue'
import Button from '@/Atoms/Decretacoes/Button.vue'
import Input from '@/Atoms/Decretacoes/Input.vue'
import Select from '@/Atoms/Decretacoes/Select.vue'
import RadioGroup from '@/Atoms/Decretacoes/RadioGroup.vue'
import FilterGroup from '@/Molecules/Decretacoes/Compostos/FilterGroup.vue'

const props = defineProps({
  processos: Object,
  stats: Object,
  filterOptions: Object,
  initialFilters: Object
})

const { can } = useAcl()
const { filters, activeFiltersCount, applyFilters, clearFilters } = useProcessoFilters(
  props.initialFilters || {}
)

const loading = ref(false)

const canCreate = computed(() => can('create', 'Decretacao'))

const viewProcesso = (processo: any) => {
  router.visit(route('decretacoes.show', processo.id))
}

const editProcesso = (processo: any) => {
  router.visit(route('decretacoes.edit', processo.id))
}
</script>
```

---

**Todos os exemplos acima são copiar-colar prontos para seu projeto!**

