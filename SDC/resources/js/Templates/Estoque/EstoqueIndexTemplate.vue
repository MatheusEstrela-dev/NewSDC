<template>
  <div class="estoque-container">
    <PageHeader
      title="Estoque"
      description="MVP de produtos, lotes, kits, validade e movimentacoes"
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('estoque')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <ViewModeToggle v-model="viewMode" />
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>
          <ActionButton
            module="estoque"
            resource="produtos"
            action="create"
            :allowed="canCreate"
            size="md"
            label="Novo Produto"
            @click="emit('create')"
          >
            <span class="hidden sm:inline">Novo Produto</span>
            <span class="sm:hidden">Novo</span>
          </ActionButton>
        </div>
      </template>
    </PageHeader>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Estoque"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <EstoqueStatsCards
      :statistics="statistics"
      :loading="loading"
    />

    <EstoqueFiltersSection
      v-model:filters="localFilters"
      :filter-options="filterOptions"
      @apply="handleApplyFilters"
      @clear="handleClearFilters"
    />


    <EstoqueAlertsPanel v-if="activeSection === 'dashboard'" :alertas="alertas" class="mb-6" />


    <EstoqueGrid
        v-if="(activeSection === 'dashboard' || activeSection === 'produtos') && (viewMode === 'grid' || isMobile)"
      :produtos="produtosPaginados"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-move="canMove"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
      @move="emit('move', $event)"
    />

    <EstoqueTable
      v-else-if="activeSection === 'dashboard' || activeSection === 'produtos'"
      :produtos="produtosPaginados"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-move="canMove"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
      @move="emit('move', $event)"
    />

    <Pagination
      v-if="activeSection === 'dashboard' || activeSection === 'produtos'"
      :pagination="pagination"
      @page-change="goToPage"
    />

    <div v-if="activeSection === 'dashboard'" class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.25fr_0.75fr]">
      <EstoqueKitsPanel
      :kits="kits"
      :can-assemble="canAssemble"
      @assemble="emit('assemble', $event)"
    />
      <EstoqueMovementsPanel :movimentacoes="movimentacoes" />
    </div>

    <EstoqueKitsPanel
      v-else-if="activeSection === 'kits'"
      class="mt-6"
      :kits="kits"
      :can-assemble="canAssemble"
      @assemble="emit('assemble', $event)"
    />

    <EstoqueMovementsPanel
      v-else-if="activeSection === 'movimentacoes'"
      class="mt-6"
      :movimentacoes="movimentacoes"
    />
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import EstoqueAlertsPanel from '@/Components/Organisms/Estoque/EstoqueAlertsPanel.vue';
import EstoqueFiltersSection from '@/Components/Organisms/Estoque/EstoqueFiltersSection.vue';
import EstoqueGrid from '@/Components/Organisms/Estoque/EstoqueGrid.vue';
import EstoqueKitsPanel from '@/Components/Organisms/Estoque/EstoqueKitsPanel.vue';
import EstoqueMovementsPanel from '@/Components/Organisms/Estoque/EstoqueMovementsPanel.vue';
import EstoqueStatsCards from '@/Components/Organisms/Estoque/EstoqueStatsCards.vue';
import EstoqueTable from '@/Components/Organisms/Estoque/EstoqueTable.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import { useExport } from '@/Composables/useExport';
import { useMobile } from '@/Composables/useMobile';
import { usePagination } from '@/Composables/data/usePagination';
import { ArchiveBoxIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { ref, toRef, watch } from 'vue';

const props = defineProps({
  produtos: {
    type: Array,
    default: () => [],
  },
  kits: {
    type: Array,
    default: () => [],
  },
  movimentacoes: {
    type: Array,
    default: () => [],
  },
  alertas: {
    type: Array,
    default: () => [],
  },
  activeSection: {
    type: String,
    default: 'dashboard',
  },
  excelSheets: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({
      produtos: 0,
      saldo_total: 0,
      kits_montaveis: 0,
      lotes_vencendo: 0,
      requisicoes: 0,
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
  loading: {
    type: Boolean,
    default: false,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canExport: {
    type: Boolean,
    default: false,
  },
  canMove: {
    type: Boolean,
    default: false,
  },
  canAssemble: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'filter-change',
  'clear-filters',
  'create',
  'edit',
  'delete',
  'move',
  'assemble',
]);

const { isMobile } = useMobile();
const viewMode = ref('table');
const localFilters = ref({ ...props.filters });

const {
  pagination,
  paginatedItems: produtosPaginados,
  goToPage,
} = usePagination(toRef(props, 'produtos'));

const {
  showExportModal,
  handleExport: triggerExport,
} = useExport('estoque.export-excel');

watch(
  () => props.filters,
  (filters) => {
    localFilters.value = { ...filters };
  },
  { deep: true }
);

function handleApplyFilters(filters) {
  emit('filter-change', filters);
}

function handleClearFilters() {
  localFilters.value = {};
  emit('clear-filters');
}

function handleExportCsv(params) {
  triggerExport(params, localFilters.value);
}
</script>

<style scoped>
.estoque-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>


