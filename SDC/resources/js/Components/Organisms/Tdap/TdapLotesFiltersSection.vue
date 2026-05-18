<template>
  <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Ata"
      type="select"
      :model-value="localFilters.ata_id ?? ''"
      :options="ataOptions"
      placeholder="Todas as atas"
      @update:model-value="updateFilter('ata_id', $event)"
    />

    <FilterField
      label="Prestador"
      type="select"
      :model-value="localFilters.prestador_id ?? ''"
      :options="prestadorOptions"
      placeholder="Todos os prestadores"
      @update:model-value="updateFilter('prestador_id', $event)"
    />

    <FilterField
      label="Status"
      type="select"
      :model-value="localFilters.ativo ?? ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('ativo', $event)"
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
  prestadores: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const localFilters = ref({ ...props.filters });

const statusOptions = [
  { value: '1', label: 'Ativos' },
  { value: '0', label: 'Inativos' },
];

const ataOptions = computed(() =>
  props.atas.map((a) => ({ value: a.id, label: a.numero }))
);

const prestadorOptions = computed(() =>
  props.prestadores.map((p) => ({ value: p.id, label: p.nome }))
);

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
  apply();
}

function apply() {
  emit('apply', cleanFilters(localFilters.value));
}

function clear() {
  localFilters.value = {};
  emit('clear');
}
</script>
