<template>
  <div class="processos-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Reconhecimentos de Desastre"
      description="Gerencie os processos de decretação de emergência e calamidade pública"
      :icon="ExclamationTriangleIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Oculto em mobile -->
          <div class="hidden md:flex items-center gap-1 bg-white dark:bg-slate-800/50 rounded-lg p-1 border border-slate-300 dark:border-slate-700/50">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all',
                viewMode === 'grid'
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
              ]"
              title="Visualização em Grade"
            >
              Grade
            </button>
            <button
              @click="viewMode = 'table'"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all',
                viewMode === 'table'
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
              ]"
              title="Visualização em Tabela"
            >
              Tabela
            </button>
          </div>

          <!-- Botão Exportar -->
          <Button variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botão Criar - Responsivo -->
          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="$emit('create')"
          >
            <span class="hidden sm:inline">Novo Processo</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Decretações"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Statistics Cards -->
    <DecretacoesStatsCards
      :statistics="statistics"
      :loading="loading"
      @filter="handleStatFilter"
    />

    <!-- Filters -->
    <ProcessoFilters
      v-model:filters="localFilters"
      :filter-options="filterOptions"
      @apply="handleApplyFilters"
      @clear="handleClearFilters"
      class="mb-6"
    />

    <!-- Mobile: Sempre Grade | Desktop: Grade ou Tabela -->
    <ProcessoGrid
      v-if="viewMode === 'grid' || isMobile"
      :processos="processos"
      :loading="loading"
      :can-edit="canEdit"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <ProcessoTable
      v-else-if="viewMode === 'table' && !isMobile"
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
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import DecretacoesStatsCards from '@/Components/Organisms/Decretacoes/DecretacoesStatsCards.vue';
import ProcessoFilters from '@/Components/Organisms/Decretacoes/ProcessoFilters.vue';
import ProcessoGrid from '@/Components/Organisms/Decretacoes/ProcessoGrid.vue';
import ProcessoTable from '@/Components/Organisms/Decretacoes/ProcessoTable.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useMobile } from '@/composables/useMobile';

// Detecção mobile
const { isMobile } = useMobile();

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
  canCreate: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['filter-change', 'clear-filters', 'page-change', 'view', 'edit', 'create']);

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

// =========================
// Modal de Exportação CSV (Usando Composable)
// =========================
import { useExport } from '@/Composables/useExport';

const {
  showExportModal,
  handleExport: triggerExport
} = useExport('decretacoes.export');

function handleExportCsv(params) {
  triggerExport(params, localFilters.value);
}
</script>

<style scoped>
.processos-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  /* Padding removed for global alignment */
}
</style>
