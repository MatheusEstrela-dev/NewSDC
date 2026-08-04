<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Busca"
      type="text"
      :model-value="filters.search || ''"
      placeholder="Título ou descrição"
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Categoria"
      type="select"
      :model-value="filters.categoria || ''"
      :options="categoriaOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('categoria', $event)"
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

    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-1">
      <FilterActions @search="handleSearch" @clear="handleClear" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import { computed } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({ tipos: [], status: [], categorias: [] }),
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const tipoOptions = computed(() => [
  { value: '', label: 'Todos' },
  ...(props.filterOptions.tipos || []).map((t) => ({ value: t.value, label: t.label })),
]);

const statusOptions = computed(() => [
  { value: '', label: 'Todos' },
  ...(props.filterOptions.status || []).map((s) => ({ value: s.value, label: s.label })),
]);

const categoriaOptions = computed(() => [
  { value: '', label: 'Todas' },
  ...(props.filterOptions.categorias || []).map((c) => ({ value: c.value, label: c.label })),
]);

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
