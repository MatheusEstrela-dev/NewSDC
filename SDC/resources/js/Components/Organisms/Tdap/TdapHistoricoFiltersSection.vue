<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Tipo de entidade"
      type="select"
      :model-value="localFilters.entity_type ?? ''"
      :options="entityTypeOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('entity_type', $event)"
    />

    <FilterField
      label="Tipo de evento"
      type="text"
      :model-value="localFilters.tipo_evento || ''"
      placeholder="ex: cronograma.ativado"
      @update:model-value="updateFilter('tipo_evento', $event)"
    />

    <FilterField
      label="De"
      type="date"
      :model-value="localFilters.de || ''"
      @update:model-value="updateFilter('de', $event)"
    />

    <FilterField
      label="Até"
      type="date"
      :model-value="localFilters.ate || ''"
      @update:model-value="updateFilter('ate', $event)"
    />

    <div class="md:col-span-4 flex justify-end">
      <FilterActions @search="apply" @clear="clear" />
    </div>
  </FilterSection>
</template>

<script setup>
import { ref, watch } from 'vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const localFilters = ref({ ...props.filters });

const entityTypeOptions = [
  { value: 'cronograma', label: 'Cronograma' },
  { value: 'viagem', label: 'Viagem' },
  { value: 'vistoria', label: 'Vistoria' },
  { value: 'crono_caminhao', label: 'Caminhão alocado' },
];

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

  if (key === 'tipo_evento') {
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
