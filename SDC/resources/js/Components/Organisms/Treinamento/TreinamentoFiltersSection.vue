<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Título"
      type="text"
      :model-value="filters.titulo || ''"
      placeholder="Título do treinamento"
      @update:model-value="updateFilter('titulo', $event)"
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
      label="Status"
      type="select"
      :model-value="filters.status || ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('status', $event)"
    />
    
    <FilterField
      label="Instrutor"
      type="text"
      :model-value="filters.instrutor || ''"
      placeholder="Nome do instrutor"
      @update:model-value="updateFilter('instrutor', $event)"
    />
    
    <FormDateRange
      class="md:col-span-2"
      label="Período"
      start-label="Data Início"
      end-label="Data Fim"
      :model-value="{ start: filters.data_inicio || '', end: filters.data_fim || '' }"
      @update:model-value="handleDateRangeChange"
    />
    
    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-6">
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

const tipoOptions = [
  { value: '', label: 'Todos' },
  { value: 'PRESENCIAL', label: 'Presencial' },
  { value: 'EAD', label: 'EAD' },
  { value: 'HIBRIDO', label: 'Híbrido' },
];

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'PLANEJADO', label: 'Planejado' },
  { value: 'EM_ANDAMENTO', label: 'Em Andamento' },
  { value: 'CONCLUIDO', label: 'Concluído' },
  { value: 'CANCELADO', label: 'Cancelado' },
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
