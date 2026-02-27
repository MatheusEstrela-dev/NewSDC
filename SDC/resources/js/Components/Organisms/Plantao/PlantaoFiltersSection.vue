<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Plantonista"
      type="text"
      :model-value="filters.plantonista_nome || ''"
      placeholder="Nome do plantonista"
      @update:model-value="updateFilter('plantonista_nome', $event)"
    />
    
    <FilterField
      label="Período (Turno)"
      type="select"
      :model-value="filters.periodo || ''"
      :options="periodoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('periodo', $event)"
    />
    
    <FilterField
      label="Status"
      type="select"
      :model-value="filters.status || ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('status', $event)"
    />
    
    <FormDateRange
      class="md:col-span-1"
      start-label="Data Início"
      end-label="Data Fim"
      :model-value="{ start: filters.data_inicio || '', end: filters.data_fim || '' }"
      @update:model-value="handleDateRangeChange"
    />
    
    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-1">
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
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const periodoOptions = [
  { value: '', label: 'Todos' },
  { value: 'DIURNO', label: 'Diurno' },
  { value: 'NOTURNO', label: 'Noturno' },
  { value: 'EXTRAORDINARIO', label: 'Extraordinário' },
];

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'ATIVO', label: 'Ativo' },
  { value: 'FINALIZADO', label: 'Finalizado' },
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
