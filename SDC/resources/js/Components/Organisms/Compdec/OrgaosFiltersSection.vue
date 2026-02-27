<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Nome ou Código"
      type="text"
      :model-value="filters.search || ''"
      placeholder="Buscar por nome ou código"
      @update:model-value="updateFilter('search', $event)"
    />
    
    <FilterField
      label="Tipo de Órgão"
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
      label="Município"
      type="select"
      :model-value="filters.municipio || ''"
      :options="municipalities"
      placeholder="Todos"
      @update:model-value="updateFilter('municipio', $event)"
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

const tipoOptions = [
  { value: '', label: 'Todos' },
  { value: 'compdec', label: 'COMPDEC' },
  { value: 'redec', label: 'REDEC' },
  { value: 'cedec', label: 'CEDEC' },
];

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'inativo', label: 'Inativo' },
  { value: 'em_implantacao', label: 'Em Implantação' },
  { value: 'suspenso', label: 'Suspenso' },
];

function updateFilter(key, value) {
  emit('filter-change', { ...props.filters, [key]: value });
}

function handleSearch() {
  emit('filter-change', props.filters);
}

function handleClear() {
  emit('filter-reset');
}
</script>
