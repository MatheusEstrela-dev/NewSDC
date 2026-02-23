<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Nome"
      type="text"
      :model-value="filters.nome || ''"
      placeholder="Nome do beneficiário"
      @update:model-value="updateFilter('nome', $event)"
    />
    
    <FilterField
      label="CPF"
      type="text"
      :model-value="filters.cpf || ''"
      placeholder="000.000.000-00"
      @update:model-value="updateFilter('cpf', $event)"
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
      label="Município"
      type="select"
      :model-value="filters.municipio || ''"
      :options="municipalities"
      placeholder="Todos"
      @update:model-value="updateFilter('municipio', $event)"
    />
    
    <FilterField
      label="Situação"
      type="select"
      :model-value="filters.situacao || ''"
      :options="situacaoOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('situacao', $event)"
    />
    
    <FormDateRange
      class="md:col-span-2"
      label="Período de Cadastro"
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
  municipalities: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'ATIVO', label: 'Ativo' },
  { value: 'INATIVO', label: 'Inativo' },
  { value: 'PENDENTE', label: 'Pendente' },
];

const situacaoOptions = [
  { value: '', label: 'Todas' },
  { value: 'EM_ABRIGO', label: 'Em Abrigo' },
  { value: 'FORA_ABRIGO', label: 'Fora de Abrigo' },
  { value: 'DESALOJADO', label: 'Desalojado' },
  { value: 'DESABRIGADO', label: 'Desabrigado' },
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
