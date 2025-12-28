<template>
  <div class="processos-container">
    <!-- Header -->
    <div class="mb-4 md:mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div class="flex-1 min-w-0">
        <Heading :level="2" color="default" class="mb-1 md:mb-2 text-xl md:text-2xl">
          Reconhecimentos de Desastre
        </Heading>
        <Text size="sm" color="muted" class="hidden sm:block">
          Gerencie os processos de decretação de emergência e calamidade pública
        </Text>
      </div>

      <!-- View Toggle -->
      <div class="flex items-center gap-2 bg-white dark:bg-slate-800/50 rounded-lg p-1 border border-slate-300 dark:border-slate-700/50 self-start md:self-auto">
        <button
          @click="viewMode = 'grid'"
          :class="[
            'px-2.5 md:px-3 py-1.5 rounded text-xs font-medium transition-all touch-manipulation',
            viewMode === 'grid'
              ? 'bg-primary-600 text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
          ]"
          title="Visualização em Grade"
        >
          <span class="hidden sm:inline">Grade</span>
          <span class="sm:hidden">⊞</span>
        </button>
        <button
          @click="viewMode = 'table'"
          :class="[
            'px-2.5 md:px-3 py-1.5 rounded text-xs font-medium transition-all touch-manipulation',
            viewMode === 'table'
              ? 'bg-primary-600 text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
          ]"
          title="Visualização em Tabela"
        >
          <span class="hidden sm:inline">Tabela</span>
          <span class="sm:hidden">☰</span>
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <ProcessoStatsCards
      :statistics="statistics"
      @filter="handleStatFilter"
      class="mb-6"
    />

    <!-- Filters -->
    <ProcessoFilters
      v-model:filters="localFilters"
      :filter-options="filterOptions"
      @apply="handleApplyFilters"
      @clear="handleClearFilters"
      class="mb-6"
    />

    <!-- Grid ou Table -->
    <ProcessoGrid
      v-if="viewMode === 'grid'"
      :processos="processos"
      :loading="loading"
      :can-edit="canEdit"
    />

    <ProcessoTable
      v-else
      :processos="processos"
      :can-edit="canEdit"
      @view="(id) => $emit('view', id)"
      @edit="(id) => $emit('edit', id)"
    />

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ProcessoStatsCards from '@/Components/Organisms/Decretacoes/ProcessoStatsCards.vue';
import ProcessoFilters from '@/Components/Organisms/Decretacoes/ProcessoFilters.vue';
import ProcessoGrid from '@/Components/Organisms/Decretacoes/ProcessoGrid.vue';
import ProcessoTable from '@/Components/Organisms/Decretacoes/ProcessoTable.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';

const props = defineProps({
  processos: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      vigentes: 0,
      vencidos: 0,
      proximos_vencer: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
  pagination: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['filter-change', 'clear-filters', 'page-change', 'view', 'edit']);

const localFilters = ref({ ...props.filters });
const viewMode = ref('grid');

const handleApplyFilters = (filters) => {
  emit('filter-change', filters);
};

const handleClearFilters = () => {
  localFilters.value = {};
  emit('clear-filters');
};

const handlePageChange = (page) => {
  emit('page-change', page);
};

const handleStatFilter = (type) => {
  localFilters.value.vigencia_status = type === 'all' ? '' : type;
  handleApplyFilters(localFilters.value);
};
</script>

<style scoped>
.processos-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  padding: 1rem;
}

@media (min-width: 640px) {
  .processos-container {
    padding: 1.5rem;
  }
}

@media (min-width: 768px) {
  .processos-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .processos-container {
    padding: 2rem 3rem;
  }
}
</style>
