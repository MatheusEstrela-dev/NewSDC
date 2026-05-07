<template>
  <div class="rat-index-container">
    <!-- Header Padronizado -->


    <PageHeader
      :title="MESSAGES.rat.title"
      :description="MESSAGES.rat.description"
      :icon="DocumentTextIcon"
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

          <!-- Botao Criar - Responsivo -->
          <Link v-if="canCreate" :href="route('rat.create')">
            <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
              <span class="hidden sm:inline">Novo RAT</span>
              <span class="sm:hidden">Novo</span>
            </Button>
          </Link>
        </div>
      </template>
    </PageHeader>

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="RAT"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <RatStatisticsCards :statistics="statisticsToUse" />
    <RatFiltersSection
      :filters="filtersToUse"
      :municipalities="municipalities"
      :cobrade-types="cobradeTypes"
      :years="years"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />
    <!-- Visualização em Grade -->
    <RatGrid
      v-if="viewMode === 'grid'"
      :rats="ratsToUse"
      :loading="loading"
      :pagination="paginationToUse"
      :can-edit="canEdit && !useMock"
      :can-delete="!useMock"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @attachments="handleAttachments"
      @delete="handleDelete"
    />

    <!-- Visualização em Tabela -->
    <RatTable
      v-else
      :rats="ratsToUse"
      :loading="loading"
      :pagination="paginationToUse"
      :can-edit="canEdit && !useMock"
      :can-delete="!useMock"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @attachments="handleAttachments"
      @delete="handleDelete"
    />

    <!-- Pagination -->
    <div v-if="paginationToUse" class="mt-6">
      <Pagination
        :pagination="paginationToUse"
        @page-change="handlePageChange"
      />
    </div>

    <!-- Modal de Impressao do Boletim -->
    <PrintBoletimModal
      :show="showPrintModal"
      :ocorrencia="selectedOcorrencia"
      :loading="printLoading"
      @close="closePrintModal"
    />

    <!-- Modal de confirmação de exclusão -->
    <ConfirmDialog
      :is-open="showDeleteModal"
      variant="danger"
      title="Excluir RAT"
      message="Tem certeza que deseja excluir este RAT?"
      description="Esta ação não pode ser desfeita."
      confirm-text="Excluir"
      cancel-text="Cancelar"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useModalState } from '@/Composables/useModalState';
import { MESSAGES } from '@/constants/messages';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { useMobile } from '@/Composables/useMobile';
import { computed, ref, watch } from 'vue';
import RatFiltersSection from '../../Components/Organisms/Rat/Filters/RatFiltersSection.vue';
import RatGrid from '../../Components/Organisms/Rat/Grid/RatGrid.vue';
import PrintBoletimModal from '../../Components/Organisms/Rat/Print/PrintBoletimModal.vue';
import RatStatisticsCards from '../../Components/Organisms/Rat/Statistics/RatStatisticsCards.vue';
import RatTable from '../../Components/Organisms/Rat/Table/RatTable.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
  },
  rats: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  pagination: {
    type: Object,
    default: null,
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
  cobradeTypes: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  useMock: {
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
  canFinalize: {
    type: Boolean,
    default: false,
  },
});

// =========================
// Comportamento apenas no frontend
// =========================
const perPage = 15;
const currentPage = ref(1);
const localFilters = ref({ ...(props.filters || {}) });
const { isMobile } = useMobile();
const viewMode = ref(isMobile.value ? 'grid' : 'table'); // grid no mobile, table no desktop

// Estado para confirmação de exclusão
const showDeleteModal = ref(false);
const deletingRatId = ref(null);

watch(
  () => props.filters,
  (next) => {
    if (!props.useMock) return;
    localFilters.value = { ...(next || {}) };
  },
  { deep: true }
);

function normalize(s) {
  return String(s || '').toLowerCase().trim();
}

function parseDateSafe(value) {
  if (!value) return null;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

function getYearFromCreatedAt(createdAt) {
  const d = parseDateSafe(createdAt);
  return d ? d.getFullYear() : null;
}

function matchesFilters(rat, f) {
  const protocolo = normalize(f?.protocolo);
  const status = normalize(f?.status);
  const municipio = normalize(f?.municipio);
  const ano = normalize(f?.ano);

  if (protocolo && !normalize(rat?.protocolo).includes(protocolo)) return false;
  if (status && normalize(rat?.status) !== status) return false;
  if (municipio && normalize(rat?.local?.municipio) !== municipio) return false;

  if (ano) {
    const y = getYearFromCreatedAt(rat?.created_at);
    if (!y || String(y) !== ano) return false;
  }

  const start = parseDateSafe(f?.data_inicio);
  const end = parseDateSafe(f?.data_fim);
  if (start || end) {
    const created = parseDateSafe(rat?.created_at);
    if (!created) return false;
    if (start && created < start) return false;
    if (end && created > end) return false;
  }

  // (natureza/tipo_cobrade/criado_por) ainda não existem no mock, então ignoramos por enquanto
  return true;
}

const filteredRats = computed(() => {
  if (!props.useMock) return props.rats || [];
  const f = localFilters.value || {};
  return (props.rats || []).filter((r) => matchesFilters(r, f));
});

const statisticsToUse = computed(() => {
  const s = props.statistics;
  return {
    total:   s?.total   ?? 0,
    hoje:    s?.hoje    ?? 0,
    esteMes: s?.esteMes ?? 0,
    esteAno: s?.esteAno ?? 0,
  };
});

const paginationToUse = computed(() => {
  if (!props.useMock) return props.pagination;
  const total = filteredRats.value.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, currentPage.value), lastPage);

  return {
    current_page: safePage,
    last_page: lastPage,
    per_page: perPage,
    total,
  };
});

const ratsToUse = computed(() => {
  if (!props.useMock) return props.rats || [];
  const p = paginationToUse.value;
  const start = (p.current_page - 1) * p.per_page;
  return filteredRats.value.slice(start, start + p.per_page);
});

const filtersToUse = computed(() => (props.useMock ? localFilters.value : props.filters));

function handleFilterChange(newFilters) {
  if (props.useMock) {
    localFilters.value = { ...(newFilters || {}) };
    currentPage.value = 1;
    return;
  }

  router.get(route('rat.index'), newFilters, { preserveState: true, preserveScroll: true });
}

function handleFilterReset() {
  if (props.useMock) {
    localFilters.value = {};
    currentPage.value = 1;
    return;
  }

  router.get(route('rat.index'), {}, { preserveState: false, preserveScroll: false });
}

function handleView(id) {
  router.visit(route('rat.show', id));
}

function handleEdit(id) {
  router.visit(route('rat.edit', id));
}

function handleAttachments(id) {
  // Abrir diretamente na aba "Anexos" (id 6) no formulário de edição
  router.visit(`${route('rat.edit', id)}?tab=6`);
}

function handleDelete(id) {
  deletingRatId.value = id;
  showDeleteModal.value = true;
}

function confirmDelete() {
  if (!deletingRatId.value) return;
  showDeleteModal.value = false;
  router.delete(route('rat.destroy', deletingRatId.value), {
    onSuccess: () => { deletingRatId.value = null; },
    onError: (errors) => {
      console.error('Erro ao excluir RAT:', errors);
      alert('Não foi possível excluir o RAT. Verifique suas permissões e tente novamente.');
      deletingRatId.value = null;
    },
  });
}

// =========================
// Modal de Impressao (usando composable)
// =========================
const {
  isOpen: showPrintModal,
  data: selectedOcorrencia,
  loading: printLoading,
  close: closePrintModal,
  openWithLoading
} = useModalState();

async function handlePrint(id) {
  await openWithLoading(
    async () => {
      const url = route('rat.show-json', id);
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Erro ao carregar dados: ${response.status} ${response.statusText}`);
      }
      return response.json();
    },
    {
      onError: (error) => {
        alert(MESSAGES.errors.loadData('RAT') + ': ' + error.message);
      }
    }
  );
}

// =========================
// Modal de Exportação CSV (Usando Composable)
// =========================
import { useExport } from '@/Composables/useExport';

const {
  showExportModal,
  handleExport: triggerExport
} = useExport('rat.export');

function handleExportCsv(params) {
  // Passamos os filtros atuais da tela para serem combinados com os filtros do modal
  triggerExport(params, filtersToUse.value);
}

function handlePageChange(page) {
  if (props.useMock) {
    currentPage.value = Number(page) || 1;
    return;
  }

  router.get(route('rat.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}
</script>

<style scoped>
.rat-index-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>

