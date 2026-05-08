<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" :default-collapsed="false" class="mb-6">
    <FilterField
      label="Busca Geral"
      type="text"
      :model-value="local.search"
      placeholder="Codigo, nome ou responsavel..."
      @update:model-value="updateFilter('search', $event)"
    />

    <FilterField
      label="Tipo"
      type="select"
      :model-value="local.tipo"
      :options="tipoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('tipo', $event)"
    />

    <FilterField
      label="Status"
      type="select"
      :model-value="local.status"
      :options="statusOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('status', $event)"
    />

    <FilterField
      label="Municipio"
      type="text"
      :model-value="local.municipio_id"
      placeholder="ID do municipio"
      @update:model-value="updateFilter('municipio_id', $event)"
    />

    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-1">
      <FilterActions @search="emitChange" @clear="resetFilters" />
    </div>
  </FilterSection>
</template>

<script setup>
import { reactive, watch } from 'vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['change']);

const local = reactive({
  search: props.filters?.search ?? '',
  tipo: props.filters?.tipo ?? '',
  status: props.filters?.status ?? '',
  municipio_id: props.filters?.municipio_id ?? '',
});

watch(() => props.filters, (val) => {
  Object.assign(local, {
    search: val?.search ?? '',
    tipo: val?.tipo ?? '',
    status: val?.status ?? '',
    municipio_id: val?.municipio_id ?? '',
  });
}, { deep: true });

const tipoOptions = [
  { value: '', label: 'Todos' },
  { value: 'comunitaria', label: 'Comunitaria' },
  { value: 'individual', label: 'Individual' },
  { value: 'escolar', label: 'Escolar' },
];

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'ativa', label: 'Ativa' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'inativa', label: 'Inativa' },
  { value: 'em_obras', label: 'Em obras' },
];

function updateFilter(key, value) {
  local[key] = value;
}

function emitChange() {
  emit('change', { ...local });
}

function resetFilters() {
  Object.assign(local, { search: '', tipo: '', status: '', municipio_id: '' });
  emitChange();
}
</script>
