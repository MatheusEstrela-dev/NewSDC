<template>
  <div class="rat-index-container">
    <!-- Header Padronizado -->


    <PageHeader
      :title="MESSAGES.rat.title"
      :description="MESSAGES.rat.description"
      :icon="DocumentTextIcon"
      :icon-image="moduleIcon('rat')"
      variant="gradient"
    >
      <template #actions>
        <!-- flex-wrap: sem isso os botoes formam um unico flex item que o
             container do PageHeader nao consegue quebrar, e o card corta o
             ultimo botao nas larguras intermediarias. -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botão Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botao Arquivados - Arquivo morto do RAT legado (somente leitura) -->
          <Link v-if="canViewArquivados" :href="route('rat.arquivados.index')">
            <Button variant="warning" size="md" :icon="ArchiveBoxIcon" icon-position="left">
              <span class="hidden sm:inline">Arquivados</span>
            </Button>
          </Link>

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
      :sort="sort"
      :direction="direction"
      @ordenar="handleOrdenar"
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

    <!-- Modal de Criar Boletim Relacionado -->
    <ConfirmDialog
      :is-open="showRelacionarConfirm"
      variant="info"
      title="Criar Boletim Relacionado"
      :message="`Deseja criar um novo boletim relacionado ao ${relacionarRat?.protocolo || relacionarRat?.numero_bos || ''}?`"
      confirm-text="Sim, criar!"
      cancel-text="Cancelar"
      @confirm="confirmCreateRelacionado"
      @cancel="showRelacionarConfirm = false"
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
import { moduleIcon } from '@/Support/moduleIcons';
import { useModalState } from '@/Composables/useModalState';
import { MESSAGES } from '@/constants/messages';
import { ArchiveBoxIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { useMobile } from '@/Composables/useMobile';
import { useToast } from '@/Composables/useToast';
import { computed, ref, watch } from 'vue';
import RatFiltersSection from '../../Components/Organisms/Rat/Filters/RatFiltersSection.vue';
import RatGrid from '../../Components/Organisms/Rat/Grid/RatGrid.vue';
import PrintBoletimModal from '../../Components/Organisms/Rat/Print/PrintBoletimModal.vue';
import RatStatisticsCards from '../../Components/Organisms/Rat/Statistics/RatStatisticsCards.vue';
import RatTable from '../../Components/Organisms/Rat/Table/RatTable.vue';

const props = defineProps({
  /** Coluna ordenada, ja normalizada pela whitelist do controller. */
  sort: {
    type: String,
    default: 'data_hora',
  },

  /** Direcao atual: 'asc' ou 'desc'. */
  direction: {
    type: String,
    default: 'desc',
  },

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
  canViewArquivados: {
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

const { show: toast } = useToast();

// Estado para confirmação de exclusão
const showDeleteModal = ref(false);
const deletingRatId = ref(null);

// Estado para criar boletim relacionado
const showRelacionarConfirm = ref(false);
const relacionarRat = ref(null);
// IDs excluídos apenas no frontend (soft-delete no banco, sem reload)
const excludedIds = ref(new Set());

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
  if (!props.useMock) {
    return (props.rats || []).filter(r => !excludedIds.value.has(r.id));
  }
  const p = paginationToUse.value;
  const start = (p.current_page - 1) * p.per_page;
  return filteredRats.value
    .filter(r => !excludedIds.value.has(r.id))
    .slice(start, start + p.per_page);
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

/**
 * Ordenacao das colunas da tabela.
 *
 * Vai na URL junto dos filtros porque a ordenacao e feita no banco: a listagem e
 * paginada, e reordenar no cliente reordenaria apenas a pagina visivel.
 *
 * No modo mock nao ha ida ao servidor, entao o clique e ignorado em vez de
 * exibir uma ordem que nao corresponde aos dados.
 */
function handleOrdenar({ sort, direction }) {
  if (props.useMock) {
    return;
  }

  router.get(
    route('rat.index'),
    { ...(props.filters || {}), sort, direction },
    { preserveState: true, preserveScroll: true },
  );
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
  const rat = ratsToUse.value.find(r => r.id === id);
  if (!rat) {
    toast('RAT não encontrado.', 'error', { noIcon: true });
    return;
  }

  relacionarRat.value = rat;
  showRelacionarConfirm.value = true;
}

async function confirmCreateRelacionado() {
  if (!relacionarRat.value) return;
  showRelacionarConfirm.value = false;

  try {
    const ax = window.axios || (await import('axios')).default;
    const res = await ax.post(`/rat/${relacionarRat.value.id}/relacionar`, {});
    toast(res.data.message || 'Boletim relacionado criado com sucesso.', 'success', { noIcon: true });
    setTimeout(() => router.visit(res.data.url), 1000);
  } catch (e) {
    console.error('[confirmCreateRelacionado] Error:', {
      status: e?.response?.status,
      message: e?.response?.data?.message,
      data: e?.response?.data,
      error: e?.message
    });

    // Mostrar modal de confirmação novamente para o usuário ver a mensagem
    relacionarRat.value = relacionarRat.value;
    showRelacionarConfirm.value = true;

    const msg = e?.response?.data?.message || e?.message || 'Erro ao criar boletim relacionado.';
    toast(msg, 'error', { noIcon: true });
  }
}

function handleDelete(id) {
  deletingRatId.value = id;
  showDeleteModal.value = true;
}

async function confirmDelete() {
  if (!deletingRatId.value) return;
  const id = deletingRatId.value;
  showDeleteModal.value = false;
  deletingRatId.value = null;

  // Remove imediatamente do frontend (soft-delete — dado permanece no banco)
  excludedIds.value = new Set([...excludedIds.value, id]);

  try {
    const ax = window.axios || (await import('axios')).default;
    await ax.delete(route('rat.destroy', id), {
      headers: {
        'Accept': 'application/json',
      },
    });
    toast('RAT excluído com sucesso.', 'success', { noIcon: true });
  } catch {
    excludedIds.value.delete(id);
    excludedIds.value = new Set([...excludedIds.value]);
    toast('Erro ao excluir o RAT.', 'error', { noIcon: true });
  }
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

