<template>
  <div class="processos-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Reconhecimentos de Desastre"
      description="Gerencie os processos de decretação de emergência e calamidade pública"
      :icon="ExclamationTriangleIcon"
      :icon-image="moduleIcon('decretacoes')"
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

    <!-- Modal de Exportação CSV (inclui o escopo "Por REDEC") -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Decretações"
      allow-redec
      :redecs="filterOptions.redecs || []"
      :redec-selecionada="localFilters.redec_id || ''"
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
      @print="handlePrint"
      @delete="handleDelete"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <ProcessoTable
      v-else-if="viewMode === 'table' && !isMobile"
      :processos="processos"
      :total="pagination?.total"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :ordenado-por="localFilters.sort || 'data_entrada'"
      :direcao="localFilters.direction || 'desc'"
      @view="(id) => $emit('view', id)"
      @print="handlePrint"
      @edit="(id) => $emit('edit', id)"
      @delete="handleDelete"
      @ordenar="handleOrdenar"
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
import { moduleIcon } from '@/Support/moduleIcons';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

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

// Os filtros aplicados voltam do servidor via props. Sem este sync o
// localFilters ficava congelado no primeiro render (Inertia reaproveita o
// componente da pagina) e a exportacao usava filtros desatualizados.
watch(() => props.filters, (novos) => {
  localFilters.value = { ...(novos || {}) };
}, { deep: true });

const handleApplyFilters = (filters) => {
  localFilters.value = { ...filters };
  emit('filter-change', filters);
};

const handleClearFilters = () => {
  localFilters.value = {};
  emit('clear-filters');
};

const handlePageChange = (page) => {
  emit('page-change', page);
};

// Card de estatistica -> filtro rapido por vigencia. Preserva os demais filtros
// e substitui apenas vigencia_status. 'all' (ou vazio) limpa o filtro de vigencia.
/**
 * Atalho de filtro dos stat cards.
 *
 * Recebe um PATCH (objeto), nao uma string: os cards emitem {} para limpar,
 * { tipo_lancamento: 'registro'|'decretacao' } ou { vigencia_status: 'vigente' }.
 * Tratar o payload como string atribuia o objeto inteiro a vigencia_status e a
 * URL saia como `vigencia_status[tipo_lancamento]=registro`, sem filtrar nada.
 *
 * Os dois parametros sao reescritos a cada clique (e nao mesclados), para que um
 * card nao herde o recorte do card clicado antes dele.
 */
/**
 * Ordenacao das colunas da tabela.
 *
 * Vai junto com os demais filtros na mesma requisicao, porque a ordenacao e
 * feita no banco: a listagem e paginada em 15, e reordenar no cliente
 * reordenaria apenas a pagina visivel.
 *
 * Volta para a primeira pagina: manter a pagina atual apos trocar a ordem
 * mostraria uma fatia arbitraria do meio do novo conjunto.
 */
const handleOrdenar = ({ sort, direction }) => {
  localFilters.value = {
    ...localFilters.value,
    sort,
    direction,
    page: 1,
  };
  handleApplyFilters(localFilters.value);
};

const handleStatFilter = (patch = {}) => {
  localFilters.value = {
    ...localFilters.value,
    vigencia_status: patch.vigencia_status ?? '',
    tipo_lancamento: patch.tipo_lancamento ?? '',
  };
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

/**
 * Exporta exatamente o recorte da tela (REDEC, municipio, status, vigencia,
 * COBRADE...).
 *
 * O escopo "Por REDEC" do modal vai para outro endpoint (planilha agrupada por
 * REDEC, uma linha por municipio), por isso o desvio aqui.
 */
function handleExportCsv(params) {
  if (params?.type === 'redec') {
    handleExportRedec(params);
    return;
  }

  triggerExport(params, filtrosParaExportacao(params));
}

// =========================
// Exportação por REDEC (escopo "Por REDEC" do modal de exportação)
// =========================
const { handleExport: triggerExportRedec } = useExport('decretacoes.export.redec');

/**
 * Exporta as decretações por REDEC.
 *
 * A REDEC escolhida no modal substitui a que estiver nos filtros da tela (e
 * `null` significa "todas", então o `redec_id` da listagem precisa sair do
 * recorte — senão o CSV viria filtrado sem o usuário ter pedido).
 */
function handleExportRedec(params) {
  const { redec_id: redecId, ...escopo } = params ?? {};
  const filtros = filtrosParaExportacao(params);

  delete filtros.redec_id;

  triggerExportRedec(redecId ? { ...escopo, redec_id: redecId } : escopo, filtros);
}

/**
 * Filtros da tela prontos para a exportação.
 *
 * Em "Toda Série Histórica" os recortes de data são descartados, para não
 * herdar o período que estava filtrado na listagem.
 */
function filtrosParaExportacao(params) {
  const filtros = { ...localFilters.value };

  if (params?.all || params?.type === 'all') {
    delete filtros.data_entrada;
    delete filtros.data_inicio;
    delete filtros.data_fim;
    delete filtros.data_entrada_inicio;
    delete filtros.data_entrada_fim;
  }

  // A paginação da listagem não tem sentido no CSV.
  delete filtros.page;

  return filtros;
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
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
