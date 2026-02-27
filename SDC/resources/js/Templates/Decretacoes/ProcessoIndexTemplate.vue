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
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botão Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
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
      :can-delete="canDelete"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <ProcessoTable
      v-else-if="viewMode === 'table' && !isMobile"
      :processos="processos"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @edit="(id) => $emit('edit', id)"
    />

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import DecretacoesStatsCards from '@/Components/Organisms/Decretacoes/DecretacoesStatsCards.vue';
import ProcessoFilters from '@/Components/Organisms/Decretacoes/ProcessoFilters.vue';
import ProcessoGrid from '@/Components/Organisms/Decretacoes/ProcessoGrid.vue';
import ProcessoTable from '@/Components/Organisms/Decretacoes/ProcessoTable.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

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
    default: false,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canExport: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['filter-change', 'clear-filters', 'page-change', 'view', 'edit', 'create']);

const localFilters = ref({ ...props.filters });
const viewMode = ref('table');

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
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
