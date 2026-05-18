<template>
  <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Busca"
      type="search"
      :model-value="localFilters.search || ''"
      placeholder="Número ou empenho"
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Estado"
      type="select"
      :model-value="localFilters.estado ?? ''"
      :options="estadoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('estado', $event)"
    />

    <FilterField
      label="Ata"
      type="select"
      :model-value="localFilters.ata_id ?? ''"
      :options="ataOptions"
      placeholder="Todas as atas"
      @update:model-value="updateFilter('ata_id', $event)"
    />

    <div class="md:col-span-3 flex justify-end">
      <FilterActions @search="apply" @clear="clear" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  atas: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const localFilters = ref({ ...props.filters });

const estadoOptions = [
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'encerrado', label: 'Encerrado' },
];

const ataOptions = computed(() =>
  props.atas.map((a) => ({ value: a.id, label: a.numero }))
);

let searchTimer = null;

watch(
  () => props.filters,
  (filters) => {
    localFilters.value = { ...filters };
  },
  { deep: true }
);

watch(
  localFilters,
  (filters) => emit('update:filters', { ...filters }),
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
  clearTimeout(searchTimer);
  localFilters.value = {};
  emit('clear');
}
</script>
