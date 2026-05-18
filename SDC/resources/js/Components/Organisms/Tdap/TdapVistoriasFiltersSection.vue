<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Busca"
      type="search"
      :model-value="localFilters.search || ''"
      placeholder="Vistoriador, edital ou ficha"
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Caminhão"
      type="select"
      :model-value="localFilters.placa_id ?? ''"
      :options="caminhaoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('placa_id', $event)"
    />

    <FilterField
      label="Parecer"
      type="select"
      :model-value="localFilters.parecer ?? ''"
      :options="parecerOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('parecer', $event)"
    />

    <div class="flex items-end justify-between gap-3">
      <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
        <input
          type="checkbox"
          :checked="!!localFilters.vigente"
          class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
          @change="updateFilter('vigente', $event.target.checked ? 1 : '')"
        />
        Apenas vigentes
      </label>
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
  caminhoes: {
    type: Array,
    default: () => [],
  },
  pareceres: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const localFilters = ref({ ...props.filters });

const caminhaoOptions = computed(() =>
  props.caminhoes.map((c) => ({ value: c.id, label: c.placa }))
);

const parecerOptions = computed(() =>
  props.pareceres.map((p) => ({ value: p.value, label: p.label }))
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
