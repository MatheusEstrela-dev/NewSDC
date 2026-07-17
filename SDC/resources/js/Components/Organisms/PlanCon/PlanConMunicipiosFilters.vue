<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" :default-collapsed="true" class="mb-6">
    <FilterField
      label="Buscar municipio"
      type="text"
      :model-value="local.search"
      placeholder="Nome do municipio ou codigo IBGE"
      @update:model-value="local.search = $event"
      @keyup.enter="applyFilters"
    />

    <div class="hidden lg:block lg:col-span-2" />

    <div class="flex items-end justify-end md:col-span-2 lg:col-span-1">
      <FilterActions @search="applyFilters" @clear="clearFilters" />
    </div>
  </FilterSection>
</template>

<script setup>
import { reactive, watch } from 'vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['change']);
const local = reactive({ search: props.filters?.search ?? '' });

watch(() => props.filters, value => {
  local.search = value?.search ?? '';
}, { deep: true });

function applyFilters() {
  emit('change', { ...props.filters, search: local.search.trim() || undefined, page: 1 });
}

function clearFilters() {
  local.search = '';
  emit('change', { page: 1 });
}
</script>
