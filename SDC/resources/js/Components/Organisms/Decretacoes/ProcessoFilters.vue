<template>
  <FilterSection
    title="Filtros de Pesquisa"
    :columns="4"
    :default-collapsed="true"
    class="mb-4 sm:mb-6"
  >
    <!-- Busca Textual -->
    <FilterField
      label="Busca"
      type="text"
      :model-value="localFilters.search || ''"
      placeholder="Protocolo, analista..."
      @update:model-value="updateFilter('search', $event)"
    />

    <!-- Tipo de Processo -->
    <FilterField
      label="Tipo de Processo"
      type="select"
      :model-value="localFilters.processo || ''"
      :options="processoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('processo', $event)"
    />

    <!-- Status -->
    <FilterField
      label="Status"
      type="select"
      :model-value="localFilters.status || ''"
      :options="filterOptions.status || []"
      placeholder="Todos os status"
      @update:model-value="updateFilter('status', $event)"
    />

    <!-- Vigência -->
    <FilterField
      label="Vigência"
      type="select"
      :model-value="localFilters.vigencia_status || ''"
      :options="vigenciaOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('vigencia_status', $event)"
    />

    <!-- Período de Datas -->
    <FormDateRange
      class="md:col-span-2"
      label="Período"
      start-label="Data Início"
      end-label="Data Fim"
      :model-value="{ start: localFilters.data_inicio || '', end: localFilters.data_fim || '' }"
      @update:model-value="handleDateRangeChange"
    />

    <!-- Ações -->
    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-6">
      <FilterActions @search="applyFilters" @clear="clearFilters" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FormDateRange from '@/Components/Molecules/Form/FormDateRange.vue';
import { ref, watch } from 'vue';

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

const localFilters = ref({ ...props.filters });

const processoOptions = [
  { value: '', label: 'Todos' },
  { value: 'MUNICIPAL', label: 'Municipal' },
  { value: 'ESTADUAL', label: 'Estadual' },
];

const vigenciaOptions = [
  { value: '', label: 'Todos' },
  { value: 'vigente', label: 'Vigentes' },
  { value: 'vencido', label: 'Vencidos' },
  { value: 'proximo_vencer', label: 'Próximos ao Vencimento' },
];

function updateFilter(key, value) {
  localFilters.value = { ...localFilters.value, [key]: value };
  emit('update:filters', localFilters.value);
}

function handleDateRangeChange(value) {
  localFilters.value = {
    ...localFilters.value,
    data_inicio: value.start,
    data_fim: value.end,
  };
  emit('update:filters', localFilters.value);
}

const applyFilters = () => {
  emit('apply', localFilters.value);
};

const clearFilters = () => {
  localFilters.value = {
    search: '',
    processo: '',
    status: '',
    vigencia_status: '',
    data_inicio: '',
    data_fim: '',
  };
  emit('clear');
};

watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters };
}, { deep: true });
</script>
