<template>
  <FilterSection
    title="Filtros de Pesquisa"
    :columns="4"
    :default-collapsed="true"
    class="mb-4 sm:mb-6"
  >
    <!-- Busca Textual -->
    <FilterField label="Busca">
      <input
        v-model="localFilters.search"
        type="text"
        placeholder="Protocolo, analista..."
        class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg text-slate-200 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent"
      />
    </FilterField>

    <!-- Tipo de Processo -->
    <FilterField label="Tipo de Processo">
      <FormSelect
        v-model="localFilters.processo"
        :options="processoOptions"
        placeholder="Todos"
      />
    </FilterField>

    <!-- Status -->
    <FilterField label="Status">
      <FormSelect
        v-model="localFilters.status"
        :options="filterOptions.status || []"
        placeholder="Todos os status"
      />
    </FilterField>

    <!-- Vigência -->
    <FilterField label="Vigência">
      <FormSelect
        v-model="localFilters.vigencia_status"
        :options="vigenciaOptions"
        placeholder="Todos"
      />
    </FilterField>

    <!-- Data Início -->
    <FilterField label="Data Início">
      <input
        v-model="localFilters.data_inicio"
        type="date"
        class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent"
      />
    </FilterField>

    <!-- Data Fim -->
    <FilterField label="Data Fim">
      <input
        v-model="localFilters.data_fim"
        type="date"
        class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent"
      />
    </FilterField>

    <!-- Ações -->
    <div class="col-span-full">
      <FilterActions
        @apply="applyFilters"
        @clear="clearFilters"
      />
    </div>
  </FilterSection>
</template>

<script setup>
import { ref, watch } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';

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

const processoOptions = [
  { value: '', label: 'Todos' },
  { value: 'MUNICIPAL', label: 'Municipal' },
  { value: 'ESTADUAL', label: 'Estadual' },
];

const vigenciaOptions = [
  { value: '', label: 'Todos' },
  { value: 'vigente', label: 'Vigentes' },
  { value: 'vencido', label: 'Vencidos' },
  { value: 'proximo_vencer', label: 'Próximos ao Vencimento' },
];

watch(localFilters, (newFilters) => {
  emit('update:filters', newFilters);
}, { deep: true });

const applyFilters = () => {
  emit('apply', localFilters.value);
};

const clearFilters = () => {
  localFilters.value = {
    search: '',
    processo: '',
    status: '',
    vigencia_status: '',
    data_inicio: '',
    data_fim: '',
  };
  emit('clear');
};
</script>
