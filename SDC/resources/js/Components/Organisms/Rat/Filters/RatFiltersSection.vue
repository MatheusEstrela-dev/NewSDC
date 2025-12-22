<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Número do Protocolo"
      type="text"
      :model-value="filters.protocolo || ''"
      placeholder="Ex: RAT-2024-001"
      @update:model-value="updateFilter('protocolo', $event)"
    />
    
    <FormDateRange
      class="md:col-span-2 lg:col-span-2"
      label="Período"
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
    
    <FilterField
      label="Município"
      type="select"
      :model-value="filters.municipio || ''"
      :options="municipalities"
      placeholder="Todos"
      @update:model-value="updateFilter('municipio', $event)"
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
      label="Tipo COBRADE"
      type="select"
      :model-value="filters.tipo_cobrade || ''"
      :options="cobradeTypes"
      placeholder="Todos"
      @update:model-value="updateFilter('tipo_cobrade', $event)"
    />
    
    <FilterField
      label="Natureza"
      type="text"
      :model-value="filters.natureza || ''"
      placeholder="Ex: Q03027, Inundação, etc."
      @update:model-value="updateFilter('natureza', $event)"
    />
    
    <FilterField
      label="Criado por"
      type="text"
      :model-value="filters.criado_por || ''"
      placeholder="Nome do relator"
      @update:model-value="updateFilter('criado_por', $event)"
    />
    
    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-6">
      <FilterActions @search="handleSearch" @clear="handleClear" />
    </div>
  </FilterSection>
</template>

<script setup>
import { ref } from 'vue';
import FilterSection from '../../../Molecules/Filter/FilterSection.vue';
import FilterField from '../../../Molecules/Filter/FilterField.vue';
import FilterActions from '../../../Molecules/Filter/FilterActions.vue';
import FormDateRange from '../../../Molecules/Form/FormDateRange.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
  cobradeTypes: {
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
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'em_andamento', label: 'Em Andamento' },
  { value: 'finalizado', label: 'Finalizado' },
  { value: 'arquivado', label: 'Arquivado' },
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

