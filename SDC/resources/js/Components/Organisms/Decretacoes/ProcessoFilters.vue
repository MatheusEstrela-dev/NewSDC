<template>
  <CardBase variant="default" padding="sm" class="overflow-hidden">
    <!-- Header -->
    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 pb-3 text-left group transition-all duration-300
             border-b border-slate-200 dark:border-slate-700/30
             hover:border-primary-500/30 dark:hover:border-primary-500/30"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-2">
        <FunnelIcon class="w-5 h-5 text-primary-500" />
        <span class="font-semibold text-slate-800 dark:text-slate-200">Filtros de Pesquisa</span>
        <span v-if="activeFiltersCount > 0" class="px-2 py-0.5 text-xs font-medium rounded-full bg-primary-500 text-white">
          {{ activeFiltersCount }}
        </span>
      </div>
      <ChevronDownIcon
        class="w-5 h-5 text-slate-400 transition-transform duration-300"
        :class="isExpanded ? 'rotate-0' : '-rotate-90'"
      />
    </button>

    <!-- Active Filters Tags -->
    <div v-if="activeFiltersList.length > 0" class="flex flex-wrap items-center gap-2 pt-3 pb-2">
      <span
        v-for="filter in activeFiltersList"
        :key="filter.key"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-full
               bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300
               border border-primary-200 dark:border-primary-700/50"
      >
        <span class="font-medium">{{ filter.label }}:</span>
        <span>{{ filter.displayValue }}</span>
        <button
          type="button"
          class="ml-0.5 p-0.5 rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 transition-colors"
          @click.stop="removeFilter(filter.key)"
        >
          <XMarkIcon class="w-3 h-3" />
        </button>
      </span>
      <button
        v-if="activeFiltersList.length > 1"
        type="button"
        class="text-xs text-red-500 hover:text-red-600 underline"
        @click.stop="clearFilters"
      >
        Limpar todos
      </button>
    </div>

    <!-- Filter Content -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-[2000px]"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-[2000px]"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="isExpanded" class="pt-4 space-y-4 overflow-hidden">
        <!-- Filtros Basicos - Grid 4 colunas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <FilterField
            label="Busca Geral"
            type="text"
            :model-value="localFilters.search || ''"
            placeholder="Analista, municipio, protocolo..."
            @update:model-value="updateFilter('search', $event)"
          />
          <FilterField
            label="Data de Entrada"
            type="date"
            :model-value="localFilters.data_entrada || ''"
            @update:model-value="updateFilter('data_entrada', $event)"
          />
          <FilterField
            label="Tipo de Processo"
            type="select"
            :model-value="localFilters.processo || ''"
            :options="processoOptions"
            placeholder="Todos os Processos"
            @update:model-value="updateFilter('processo', $event)"
          />
          <div>
            <FilterField
              label="Status do Processo"
              type="select"
              :model-value="localFilters.reconhecimento || ''"
              :options="reconhecimentoOptions"
              placeholder="Selecione um status"
              @update:model-value="updateFilter('reconhecimento', $event)"
            />
            <p v-if="statusVigentesCount" class="mt-1 text-xs text-slate-500">
              {{ statusVigentesCount }} status vigentes; os demais aparecem como (legado)
            </p>
          </div>
        </div>

        <!-- Secao Filtros Avancados -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
          <button
            type="button"
            class="flex items-center gap-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 mb-3"
            @click="showAdvanced = !showAdvanced"
          >
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            Filtros Avancados
            <ChevronDownIcon class="w-4 h-4 transition-transform" :class="showAdvanced ? 'rotate-0' : '-rotate-90'" />
          </button>

          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[500px]"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 max-h-[500px]"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-show="showAdvanced" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 overflow-hidden">
              <FilterField
                label="Analista"
                type="select"
                :model-value="localFilters.analista || ''"
                :options="analistaOptions"
                placeholder="Todos os Analistas"
                @update:model-value="updateFilter('analista', $event)"
              />
              <FilterField
                label="Situacao de Anormalidade"
                type="select"
                :model-value="localFilters.situacao_anormalidade || ''"
                :options="situacaoAnormalidadeOptions"
                placeholder="Todas as Situacoes"
                @update:model-value="updateFilter('situacao_anormalidade', $event)"
              />
              <FilterField
                label="Status de Vigencia"
                type="select"
                :model-value="localFilters.vigencia_status || ''"
                :options="vigenciaOptions"
                placeholder="Todos"
                @update:model-value="updateFilter('vigencia_status', $event)"
              />
              <FilterField
                label="REDEC"
                type="select"
                :model-value="localFilters.redec_id || ''"
                :options="redecOptions"
                placeholder="Todas as REDECs"
                @update:model-value="updateRedec($event)"
              />
              <FilterField
                :label="municipioLabel"
                type="select"
                :model-value="localFilters.municipio_id || ''"
                :options="municipioOptions"
                placeholder="Todos os Municipios"
                @update:model-value="updateMunicipio($event)"
              />
              <FilterField
                label="Data Decreto (Inicio)"
                type="date"
                :model-value="localFilters.data_decreto_inicio || ''"
                @update:model-value="updateFilter('data_decreto_inicio', $event)"
              />
              <FilterField
                label="Data Decreto (Fim)"
                type="date"
                :model-value="localFilters.data_decreto_fim || ''"
                @update:model-value="updateFilter('data_decreto_fim', $event)"
              />
              <FilterField
                label="No Protocolo FIDE"
                type="text"
                :model-value="localFilters.n_protocolo_fide || ''"
                placeholder="Ex: MG-F-3141504-13214-20231219"
                @update:model-value="updateFilter('n_protocolo_fide', $event)"
              />
              <FilterField
                label="Data Entrada (Inicio)"
                type="date"
                :model-value="localFilters.data_inicio || ''"
                @update:model-value="updateFilter('data_inicio', $event)"
              />
              <FilterField
                label="Data Entrada (Fim)"
                type="date"
                :model-value="localFilters.data_fim || ''"
                @update:model-value="updateFilter('data_fim', $event)"
              />
            </div>
          </Transition>
        </div>

        <!-- Secao Filtro COBRADE -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
          <button
            type="button"
            class="flex items-center gap-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 mb-3"
            @click="showCobrade = !showCobrade"
          >
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            Filtro COBRADE
            <span v-if="selectedCobradeIds.length > 0" class="px-1.5 py-0.5 text-xs rounded bg-primary-500 text-white">
              {{ selectedCobradeIds.length }}
            </span>
            <ChevronDownIcon class="w-4 h-4 transition-transform" :class="showCobrade ? 'rotate-0' : '-rotate-90'" />
          </button>

          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[600px]"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 max-h-[600px]"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-show="showCobrade" class="space-y-3 overflow-hidden">
              <!-- Quick Buttons -->
              <CobradeQuickButtons
                :quick-filters="cobradeQuickFilters"
                v-model="selectedCobradeIds"
              />
              <!-- Tree View -->
              <CobradeFilter
                :hierarchy="cobradeHierarchy"
                v-model="selectedCobradeIds"
              />
            </div>
          </Transition>
        </div>

        <!-- Acoes -->
        <div class="flex justify-end items-end pt-4 border-t border-slate-200 dark:border-slate-700">
          <FilterActions @search="applyFilters" @clear="clearFilters" />
        </div>
      </div>
    </Transition>
  </CardBase>
</template>

<script setup>
import { computed, ref, watch, Transition } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CobradeFilter from '@/Components/Molecules/Filter/CobradeFilter.vue';
import CobradeQuickButtons from '@/Components/Molecules/Filter/CobradeQuickButtons.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import AdjustmentsHorizontalIcon from '@heroicons/vue/24/outline/AdjustmentsHorizontalIcon';
import ChevronDownIcon from '@/Components/Icons/ChevronDownIcon.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const isExpanded = ref(false);
const showAdvanced = ref(false);
const showCobrade = ref(false);
const localFilters = ref({ ...props.filters });

const selectedCobradeIds = computed({
  get() {
    const ids = localFilters.value.tipo_desastre_id;
    if (!ids) return [];
    if (Array.isArray(ids)) return ids;
    return String(ids).split(',').map(Number).filter(Boolean);
  },
  set(value) {
    localFilters.value.tipo_desastre_id = value.length > 0 ? value.join(',') : '';
  },
});

const processoOptions = [
  { value: '', label: 'Todos os Processos' },
  { value: 'MUNICIPAL', label: 'Municipal' },
  { value: 'ESTADUAL', label: 'Estadual' },
];

const vigenciaOptions = computed(() => {
  const options = props.filterOptions.vigencia_status_options || [];
  return [{ value: '', label: 'Todos' }, ...options.map(o => ({ value: o.value, label: o.label }))];
});

const situacaoAnormalidadeOptions = computed(() => {
  const options = props.filterOptions.situacoes_anormalidade || [];
  return [{ value: '', label: 'Todas as Situacoes' }, ...options.map(o => ({ value: o.value, label: o.label }))];
});

const analistaOptions = computed(() => {
  const options = props.filterOptions.analistas || [];
  return [{ value: '', label: 'Todos os Analistas' }, ...options];
});

// Status do processo: o backend entrega primeiro os status VIGENTES (enum
// StatusProcesso) e depois os legados ainda presentes no banco, marcados
// com "(legado)". Aceita tambem o formato antigo (lista de strings).
const reconhecimentoOptions = computed(() => {
  const options = props.filterOptions.reconhecimentos || [];

  return [
    { value: '', label: 'Todos os Status' },
    ...options.map(r => (
      typeof r === 'string'
        ? { value: r, label: r }
        : { value: r.value, label: r.label, vigente: r.vigente }
    )),
  ];
});

const statusVigentesCount = computed(
  () => reconhecimentoOptions.value.filter(o => o.vigente).length
);

const redecOptions = computed(() => {
  const options = props.filterOptions.redecs || [];
  return [{ value: '', label: 'Todas as REDECs' }, ...options];
});

// Correspondencia municipio <-> REDEC: cada municipio traz `redec_id`
// (ProcessoFilter::getMunicipiosOptions). Com uma REDEC escolhida, a lista de
// municipios mostra apenas os dela; escolher um municipio preenche a REDEC.
const municipiosDaRedec = computed(() => {
  const municipios = props.filterOptions.municipios || [];
  const redecId = localFilters.value.redec_id;

  if (!redecId) {
    // Sem REDEC escolhida, cada municipio mostra a sua (ex: "Betim - 1ª REDEC").
    return municipios.map(m => (
      m.redec_sigla ? { ...m, label: `${m.label} - ${m.redec_sigla}` } : m
    ));
  }

  const filtrados = municipios.filter(m => String(m.redec_id) === String(redecId));
  return filtrados.length > 0 ? filtrados : municipios;
});

const municipioOptions = computed(() => (
  [{ value: '', label: 'Todos os Municipios' }, ...municipiosDaRedec.value]
));

const municipioLabel = computed(() => {
  const redecId = localFilters.value.redec_id;
  if (!redecId) return 'Municipio';

  const sigla = redecOptions.value.find(o => String(o.id ?? o.value) === String(redecId))?.sigla;
  return sigla ? `Municipio (${sigla})` : 'Municipio';
});

function updateRedec(value) {
  const municipios = props.filterOptions.municipios || [];
  const atual = municipios.find(m => String(m.id) === String(localFilters.value.municipio_id));

  // Municipio selecionado que nao pertence a nova REDEC e descartado.
  const municipioId = value && atual?.redec_id && String(atual.redec_id) !== String(value)
    ? ''
    : localFilters.value.municipio_id || '';

  localFilters.value = { ...localFilters.value, redec_id: value, municipio_id: municipioId };
}

function updateMunicipio(value) {
  const municipios = props.filterOptions.municipios || [];
  const municipio = municipios.find(m => String(m.id) === String(value));

  localFilters.value = {
    ...localFilters.value,
    municipio_id: value,
    // Municipio define a REDEC quando ha correspondencia cadastrada.
    redec_id: municipio?.redec_id ?? localFilters.value.redec_id ?? '',
  };
}

const cobradeQuickFilters = computed(() => props.filterOptions.cobrade_quick_filters || []);
const cobradeHierarchy = computed(() => props.filterOptions.tipos_desastre_hierarquico || []);

const filterLabels = {
  search: 'Busca',
  data_entrada: 'Data Entrada',
  processo: 'Tipo',
  reconhecimento: 'Status',
  analista: 'Analista',
  situacao_anormalidade: 'Situacao',
  vigencia_status: 'Vigencia',
  data_decreto_inicio: 'Decreto Inicio',
  data_decreto_fim: 'Decreto Fim',
  data_inicio: 'Entrada Inicio',
  data_fim: 'Entrada Fim',
  municipio_id: 'Municipio',
  redec_id: 'REDEC',
  n_protocolo_fide: 'FIDE',
  tipo_desastre_id: 'COBRADE',
};

const activeFiltersList = computed(() => {
  const filters = [];
  Object.entries(localFilters.value).forEach(([key, value]) => {
    if (!value || value === '') return;
    let displayValue = value;
    if (key === 'processo') {
      displayValue = processoOptions.find(o => o.value === value)?.label || value;
    } else if (key === 'vigencia_status') {
      displayValue = vigenciaOptions.value.find(o => o.value === value)?.label || value;
    } else if (key === 'situacao_anormalidade') {
      displayValue = situacaoAnormalidadeOptions.value.find(o => o.value === value)?.label || value;
    } else if (key === 'municipio_id') {
      const municipios = props.filterOptions.municipios || [];
      displayValue = municipios.find(m => String(m.id) === String(value))?.label || value;
    } else if (key === 'redec_id') {
      displayValue = redecOptions.value.find(o => String(o.id ?? o.value) === String(value))?.label || value;
    } else if (key === 'tipo_desastre_id') {
      const ids = String(value).split(',').map(Number).filter(Boolean);
      displayValue = `${ids.length} tipo(s)`;
    }
    filters.push({ key, label: filterLabels[key] || key, displayValue });
  });
  return filters;
});

const activeFiltersCount = computed(() => activeFiltersList.value.length);

function updateFilter(key, value) {
  localFilters.value = { ...localFilters.value, [key]: value };
}

function removeFilter(key) {
  const newFilters = { ...localFilters.value };
  delete newFilters[key];
  localFilters.value = newFilters;
  applyFilters();
}

function applyFilters() {
  // Remove empty values before applying
  const cleanFilters = Object.fromEntries(
    Object.entries(localFilters.value).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );
  emit('apply', cleanFilters);
}

function clearFilters() {
  localFilters.value = {};
  emit('clear');
}

watch(
  () => props.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters };
  },
  { deep: true }
);
</script>
