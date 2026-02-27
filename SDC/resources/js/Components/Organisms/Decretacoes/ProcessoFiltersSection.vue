<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Protocolo"
      type="text"
      :model-value="filters.protocolo || ''"
      placeholder="Número do protocolo"
      @update:model-value="updateFilter('protocolo', $event)"
    />
    
    <FilterField
      label="Status"
      type="select"
      :model-value="filters.status || ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('status', $event)"
    />
    
    <FilterField
      label="Tipo"
      type="select"
      :model-value="filters.tipo || ''"
      :options="tipoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('tipo', $event)"
    />
    
    <FilterField
      label="Município"
      type="select"
      :model-value="filters.municipio || ''"
      :options="municipalities"
      placeholder="Todos"
      @update:model-value="updateFilter('municipio', $event)"
    />
    
    <FormDateRange
      class="md:col-span-2"
      start-label="Data Início"
      end-label="Data Fim"
      :model-value="{ start: filters.data_inicio || '', end: filters.data_fim || '' }"
      @update:model-value="handleDateRangeChange"
    />
    
    <FilterField
      label="Ano"
      type="select"
      :model-value="filters.ano || ''"
      :options="years"
      placeholder="Todos"
      @update:model-value="updateFilter('ano', $event)"
    />
    
    <div class="md:col-span-1 lg:col-span-4 flex justify-end items-end pt-1">
      <FilterActions @search="handleSearch" @clear="handleClear" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FormDateRange from '@/Components/Molecules/Form/FormDateRange.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'PENDENTE', label: 'Pendente' },
  { value: 'EM_ANALISE', label: 'Em Análise' },
  { value: 'APROVADO', label: 'Aprovado' },
  { value: 'REJEITADO', label: 'Rejeitado' },
  { value: 'PUBLICADO', label: 'Publicado' },
];

const tipoOptions = [
  { value: '', label: 'Todos' },
  { value: 'DECRETO', label: 'Decreto' },
  { value: 'PORTARIA', label: 'Portaria' },
  { value: 'RESOLUCAO', label: 'Resolução' },
];

function updateFilter(key, value) {
  emit('filter-change', { ...props.filters, [key]: value });
}

function handleDateRangeChange(value) {
  emit('filter-change', {
    ...props.filters,
    data_inicio: value.start,
    data_fim: value.end,
  });
}

function handleSearch() {
  emit('filter-change', props.filters);
}

function handleClear() {
  emit('filter-reset');
}
</script>
