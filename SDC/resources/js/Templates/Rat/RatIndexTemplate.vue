<template>
  <div class="rat-index-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Gestao de RAT"
      description="Visualize e gerencie todos os Registros de Atendimento Tecnico"
      :icon="DocumentTextIcon"
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

          <!-- Botao Criar - Responsivo -->
          <Link :href="route('rat.create')">
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
    <!-- Grid View -->
    <RatGrid
      v-if="viewMode === 'grid'"
      :rats="ratsToUse"
      :loading="loading"
      :pagination="paginationToUse"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @attachments="handleAttachments"
      @delete="handleDelete"
      @page-change="handlePageChange"
    />

    <!-- Table View -->
    <RatTable
      v-else
      :rats="ratsToUse"
      :loading="loading"
      :pagination="paginationToUse"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @attachments="handleAttachments"
      @delete="handleDelete"
      @page-change="handlePageChange"
    />

    <!-- Modal de Impressao do Boletim -->
    <PrintBoletimModal
      :show="showPrintModal"
      :ocorrencia="selectedOcorrencia"
      :loading="printLoading"
      @close="closePrintModal"
    />
  </div>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import RatStatisticsCards from '../../Components/Organisms/Rat/Statistics/RatStatisticsCards.vue';
import RatFiltersSection from '../../Components/Organisms/Rat/Filters/RatFiltersSection.vue';
import RatTable from '../../Components/Organisms/Rat/Table/RatTable.vue';
import RatGrid from '../../Components/Organisms/Rat/Grid/RatGrid.vue';
import PrintBoletimModal from '../../Components/Organisms/Rat/Print/PrintBoletimModal.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { getMockStatisticsFromRats } from '@/mocks/rat';

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
});

// =========================
// Frontend-only behavior
// =========================
const perPage = 15;
const currentPage = ref(1);
const localFilters = ref({ ...(props.filters || {}) });
const viewMode = ref('table'); // 'grid' or 'table' - default to table for RAT

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
  if (!props.useMock) return props.statistics;
  return getMockStatisticsFromRats(filteredRats.value);
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
  router.visit(route('rat.show', id));
}

function handleAttachments(id) {
  // Abrir diretamente na aba "Anexos" (id 6) no detalhe do RAT
  router.visit(`${route('rat.show', id)}?tab=6`);
}

function handleDelete(id) {
  if (confirm('Tem certeza que deseja excluir este RAT?')) {
    // TODO: Implementar delete
    console.log('Delete RAT:', id);
  }
}

// =========================
// Modal de Impressao
// =========================
const showPrintModal = ref(false);
const selectedOcorrencia = ref(null);
const printLoading = ref(false);

async function handlePrint(id) {
  showPrintModal.value = true;
  printLoading.value = true;
  selectedOcorrencia.value = null;

  try {
    const url = route('rat.show.json', id);
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Erro ao carregar dados: ${response.status} ${response.statusText}`);
    }
    const data = await response.json();
    selectedOcorrencia.value = data;
  } catch (error) {
    alert('Erro ao carregar dados do boletim: ' + error.message);
    showPrintModal.value = false;
  } finally {
    printLoading.value = false;
  }
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

function closePrintModal() {
  showPrintModal.value = false;
  selectedOcorrencia.value = null;
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
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  /* Padding removed to align with NavigationHeader which matches main content padding */
}
</style>

