<template>
  <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true" class="mb-6">
    <FilterField
      label="Busca"
      type="search"
      :model-value="localFilters.search || ''"
      placeholder="Nome, CNPJ, telefone, e-mail ou representante"
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Status"
      type="select"
      :model-value="localFilters.ativo ?? ''"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('ativo', $event)"
    />

    <!-- O backend ja aceitava `uf`, mas a tela nao tinha por onde informar. -->
    <FilterField
      label="UF"
      type="select"
      :model-value="localFilters.uf ?? ''"
      :options="ufOptions"
      placeholder="Todas"
      @update:model-value="updateFilter('uf', $event)"
    />

    <div class="flex min-h-[4.25rem] items-end justify-end md:col-span-3">
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
  /** Opcoes {value,label} vindas de App\Support\UnidadeFederativa::options(). */
  ufs: {
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
const ufOptions = computed(() => props.ufs.map((uf) => ({ value: uf.value, label: uf.label })));

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
