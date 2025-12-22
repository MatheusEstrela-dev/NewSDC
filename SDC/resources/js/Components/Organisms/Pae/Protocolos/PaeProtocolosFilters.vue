<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6" :default-collapsed="false">
    <FilterField
      label="Buscar"
      type="text"
      :model-value="filters.buscar || ''"
      placeholder="Protocolo ou nome do empreendimento"
      @update:model-value="update('buscar', $event)"
    />

    <FilterField
      label="Situação"
      type="select"
      :model-value="filters.situacao || ''"
      :options="situacoes"
      placeholder="Todas as situações"
      @update:model-value="update('situacao', $event)"
    />

    <FilterField
      label="Analista"
      type="select"
      :model-value="filters.analista || ''"
      :options="analistas"
      placeholder="Todos os analistas"
      @update:model-value="update('analista', $event)"
    />

    <FilterField
      label="Empreendedor"
      type="select"
      :model-value="filters.empreendedor || ''"
      :options="empreendedores"
      placeholder="Todos os empreendedores"
      @update:model-value="update('empreendedor', $event)"
    />

    <FormDateRange
      label="Período (Entrada)"
      start-label="Data Inicial"
      end-label="Data Final"
      :model-value="{ start: filters.data_inicio || '', end: filters.data_fim || '' }"
      @update:model-value="handleDateRangeChange"
      label-size="sm"
    />

    <div class="md:col-span-2 lg:col-span-4 flex justify-end items-end pt-6">
      <FilterActions @search="handleSearch" @clear="handleClear" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FormDateRange from '@/Components/Molecules/Form/FormDateRange.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  situacoes: {
    type: Array,
    default: () => [],
  },
  analistas: {
    type: Array,
    default: () => [],
  },
  empreendedores: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

function update(key, value) {
  emit('filter-change', { ...props.filters, [key]: value });
}

function handleDateRangeChange(value) {
  emit('filter-change', {
    ...props.filters,
    data_inicio: value.start,
    data_fim: value.end,
  });
}

function handleSearch() {
  emit('filter-change', props.filters);
}

function handleClear() {
  emit('filter-reset');
}
</script>


