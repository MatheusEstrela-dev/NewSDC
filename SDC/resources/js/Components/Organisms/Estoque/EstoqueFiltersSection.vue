<template>
  <FilterSection title="Filtros de Pesquisa" :columns="5" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Busca"
      type="search"
      :model-value="localFilters.search || ''"
      placeholder="Produto, SKU, lote ou endereco"
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Unidade"
      type="select"
      :model-value="localFilters.unidade || ''"
      :options="unidadeOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('unidade', $event)"
    />

    <FilterField
      label="Status"
      type="select"
      :model-value="localFilters.status || ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('status', $event)"
    />

    <FilterField
      label="Validade"
      type="select"
      :model-value="localFilters.validade || ''"
      :options="validadeOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('validade', $event)"
    />

    <div class="flex min-h-[4.25rem] items-end justify-end">
      <FilterActions @search="apply" @clear="clear" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import { computed, ref, watch } from 'vue';

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
let searchTimer = null;

const unidadeOptions = computed(() => props.filterOptions.unidades || []);
const statusOptions = computed(() => props.filterOptions.status || []);
const validadeOptions = computed(() => props.filterOptions.validade || []);

watch(
  () => props.filters,
  (filters) => {
    localFilters.value = { ...filters };
  },
  { deep: true }
);

watch(
  localFilters,
  (filters) => {
    emit('update:filters', { ...filters });
  },
  { deep: true }
);

function cleanFilters(filters) {
  return Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== '' && value !== null && value !== undefined)
  );
}

function updateFilter(key, value) {
  localFilters.value = { ...localFilters.value, [key]: value };

  if (key === 'search') {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(apply, 350);
    return;
  }

  apply();
}

function apply() {
  clearTimeout(searchTimer);
  emit('apply', cleanFilters(localFilters.value));
}

function clear() {
  localFilters.value = {};
  emit('clear');
}
</script>
