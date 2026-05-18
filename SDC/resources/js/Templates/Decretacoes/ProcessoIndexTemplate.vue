<template>
  <div ref="processosContainerRef" class="processos-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Reconhecimentos de Desastre"
      description="Gerencie os processos de decretação de emergência e calamidade pública"
      :icon="ExclamationTriangleIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex min-w-0 flex-wrap items-center gap-2 sm:gap-3">
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
      v-if="viewMode === 'grid' || isCompact"
      :processos="processos"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @print="handlePrint"
      @delete="handleDelete"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <ProcessoTable
      v-else-if="viewMode === 'table' && !isCompact"
      :processos="processos"
      :total="pagination?.total"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @print="handlePrint"
      @edit="(id) => $emit('edit', id)"
      @delete="handleDelete"
    />

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="handlePageChange"
      />
    </div>

    <!-- Modal de Impressao -->
    <PrintDecretacaoModal
      :show="printModalOpen"
      :processo="selectedProcesso"
      @close="closePrintModal"
    />

    <!-- Modal de Confirmacao de Exclusao -->
    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Excluir Processo"
      message="Tem certeza que deseja excluir este processo?"
      description="Esta acao ira marcar o processo como excluido. Os dados serao preservados para auditoria."
      variant="danger"
      confirm-text="Excluir"
      cancel-text="Cancelar"
      :loading="deleteLoading"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import DecretacoesStatsCards from '@/Components/Organisms/Decretacoes/DecretacoesStatsCards.vue';
import PrintDecretacaoModal from '@/Components/Organisms/Decretacoes/Print/PrintDecretacaoModal.vue';
import ProcessoFilters from '@/Components/Organisms/Decretacoes/ProcessoFilters.vue';
import ProcessoGrid from '@/Components/Organisms/Decretacoes/ProcessoGrid.vue';
import ProcessoTable from '@/Components/Organisms/Decretacoes/ProcessoTable.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ref, computed, onBeforeUnmount, onMounted } from 'vue';

// Detecção mobile
const { isMobile, isTablet, screenWidth } = useMobile();
const processosContainerRef = ref(null);
const containerWidth = ref(0);
let containerResizeObserver = null;

const isCompact = computed(() => {
  const availableWidth = containerWidth.value || screenWidth.value;
  return isMobile.value || isTablet.value || availableWidth < 1180;
});

onMounted(() => {
  if (typeof ResizeObserver === 'undefined' || !processosContainerRef.value) {
    containerWidth.value = processosContainerRef.value?.getBoundingClientRect?.().width || screenWidth.value;
    return;
  }

  containerResizeObserver = new ResizeObserver(([entry]) => {
    containerWidth.value = entry.contentRect.width;
  });
  containerResizeObserver.observe(processosContainerRef.value);
});

onBeforeUnmount(() => {
  containerResizeObserver?.disconnect();
  containerResizeObserver = null;
});

const props = defineProps({
  processos: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({
      totalEventos: 0,
      totalEventosEcp: 0,
      totalEventosSe: 0,
      registros: 0,
      registrosEcp: 0,
      registrosSe: 0,
      decretacoes: 0,
      decretacoesEcp: 0,
      decretacoesSe: 0,
      municipiosAtingidos: 0,
      municipiosAtingidosEcp: 0,
      municipiosAtingidosSe: 0,
      decretacoesVigentes: 0,
      decretacoesVigentesEcp: 0,
      decretacoesVigentesSe: 0,
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

// =========================
// Modal de Impressao
// =========================
const printModalOpen = ref(false);
const selectedProcessoId = ref(null);

const selectedProcesso = computed(() => {
  if (!selectedProcessoId.value) return null;
  return props.processos.find(p => p.id === selectedProcessoId.value) || null;
});

function handlePrint(id) {
  selectedProcessoId.value = id;
  printModalOpen.value = true;
}

function closePrintModal() {
  printModalOpen.value = false;
  selectedProcessoId.value = null;
}

// =========================
// Modal de Confirmacao de Exclusao
// =========================
const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const processoIdToDelete = ref(null);

function handleDelete(id) {
  processoIdToDelete.value = id;
  showDeleteConfirm.value = true;
}

function confirmDelete() {
  if (processoIdToDelete.value) {
    deleteLoading.value = true;
    router.delete(route('decretacoes.destroy', processoIdToDelete.value), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteConfirm.value = false;
        processoIdToDelete.value = null;
      },
      onFinish: () => {
        deleteLoading.value = false;
      },
    });
  }
}

function cancelDelete() {
  showDeleteConfirm.value = false;
  processoIdToDelete.value = null;
}
</script>

<style scoped>
.processos-container {
  @apply w-full min-w-0 overflow-x-hidden pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
