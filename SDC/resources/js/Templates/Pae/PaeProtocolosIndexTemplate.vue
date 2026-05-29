<template>
  <div class="pae-protocolos-container">
    <!-- Header Padronizado com Toggle -->
    <PageHeader
      title="Protocolos PAE"
      description="Gerencie os protocolos de análise de PAE"
      :icon="ClipboardDocumentListIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botao CCPAE - filtro rapido -->
          <Button variant="violet" size="md" @click="handleCcpaeFilter">
            CCPAE
          </Button>

          <!-- Botao Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botao Novo Protocolo - Responsivo -->
          <Button v-if="canCreate" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="openNovoProtocolo">
            <span class="hidden sm:inline">Novo Protocolo</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="PAE"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Modal Atribuir Analista -->
    <AssignAnalistaModal
      :show="showAssignModal"
      :protocolo="selectedProtocoloAssign"
      :analistas="analistas"
      @close="closeAssignModal"
      @assigned="handleAssignedAction"
    />

    <PaeProtocolosStatsCards
      :stats="statsToUse"
      @total="handleTotalProtocolos"
      @historico="handleHistoricoProtocolos"
      @vencidos="handleVencidosProtocolos"
      @ccpae="handleCcpaeFilter"
    />

    <PaeProtocolosFilters
      :filters="filters"
      :situacoes="situacoes"
      :analistas="analistas"
      :empreendedores="empreendedores"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />

    <!-- Mobile: Sempre Grade | Desktop: Grade ou Tabela -->
    <PaeProtocolosGrid
      v-if="viewMode === 'grid' || isMobile"
      :protocolos="paginatedProtocolos"
      :loading="loading"
      :pagination="paginationToUse"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-atribuir="canAtribuirComputed"
      :can-check="canCheck"
      :can-pdf="canPdf"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @check="handleCheck"
      @pdf="handlePdf"
      @archive="handleArchive"
      @delete="handleDelete"
      @options="handleOptions"
      @assign="handleAssign"
    />

    <!-- Desktop: Tabela (somente quando selecionada e nao mobile) -->
    <PaeProtocolosTable
      v-else-if="viewMode === 'table' && !isMobile"
      :protocolos="paginatedProtocolos"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-atribuir="canAtribuirComputed"
      :can-check="canCheck"
      :can-pdf="canPdf"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @check="handleCheck"
      @pdf="handlePdf"
      @archive="handleArchive"
      @delete="handleDelete"
      @options="handleOptions"
      @assign="handleAssign"
    />

    <!-- Pagination -->
    <div v-if="paginationToUse" class="mt-6">
      <Pagination
        :pagination="paginationToUse"
        @page-change="handlePageChange"
      />
    </div>

    <PaeHistoricoModal
      :open="historicoModalOpen"
      :protocolo="selectedProtocolo"
      :historico="historicoPayload"
      :external-view="isExternalView"
      @close="closeHistorico"
    />

    <PrintPaeProtocoloModal
      :show="printModalOpen"
      :protocolo="selectedProtocoloPrint"
      @close="closePrint"
    />

    <!-- Modal de Confirmacao de Exclusao -->
    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Excluir Protocolo"
      message="Tem certeza que deseja excluir este protocolo?"
      description="Esta acao marcara o protocolo como excluido. Os dados serao preservados para auditoria."
      variant="danger"
      confirm-text="Excluir"
      cancel-text="Cancelar"
      :loading="deleteLoading"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />

    <!-- Modal de Confirmacao de Arquivamento -->
    <ConfirmDialog
      :is-open="showArchiveConfirm"
      :title="protocoloJaArquivado ? 'Desarquivar Protocolo' : 'Arquivar Protocolo'"
      :message="protocoloJaArquivado ? 'Deseja desarquivar este protocolo?' : 'Deseja arquivar este protocolo?'"
      :description="protocoloJaArquivado
        ? 'O protocolo voltara a aparecer na lista ativa. Nenhum dado e perdido nesta acao.'
        : 'O protocolo saira da lista ativa, mas continuara disponivel no historico e podera ser desarquivado a qualquer momento.'"
      variant="warning"
      :confirm-text="protocoloJaArquivado ? 'Desarquivar' : 'Arquivar'"
      cancel-text="Cancelar"
      :loading="archiveLoading"
      @confirm="confirmArchive"
      @cancel="cancelArchive"
    />

    <!-- Modal de Confirmacao CCPAE -->
    <ConfirmDialog
      :is-open="showCcpaeConfirm"
      title="Concluir para CCPAE"
      message="Deseja concluir este protocolo para o CCPAE?"
      description="Esta acao alterara o status do protocolo para CCPAE, registrando a movimentacao e mantendo o historico."
      variant="success"
      confirm-text="Concluir"
      cancel-text="Cancelar"
      :loading="ccpaeLoading"
      @confirm="confirmCcpae"
      @cancel="cancelCcpae"
    />
  </div>
</template>

<script setup>
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from '@/composables/useToast';

import Button from '@/Components/Atoms/Button/Button.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';

import PrintPaeProtocoloModal from '@/Components/Organisms/Pae/Print/PrintPaeProtocoloModal.vue';
import PaeHistoricoModal from '@/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue';
import PaeProtocolosFilters from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosFilters.vue';
import PaeProtocolosGrid from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue';
import PaeProtocolosStatsCards from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue';
import PaeProtocolosTable from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue';
import AssignAnalistaModal from '@/Components/Organisms/Pae/Protocolos/AssignAnalistaModal.vue';

import { GetPaeProtocoloHistorico } from '@/domain/pae/usecases/GetPaeProtocoloHistorico';
import { ListPaeProtocolos } from '@/domain/pae/usecases/ListPaeProtocolos';
import { ApiPaeProtocoloRepository } from '@/infrastructure/pae/ApiPaeProtocoloRepository';
import { MockPaeProtocoloRepository } from '@/infrastructure/pae/MockPaeProtocoloRepository';

import {
    getMockPaeStats,
    matchesPaeFilters,
    paeAnalistas,
    paeEmpreendedores,
    paeSituacoes,
} from '@/mocks/pae';

import { useMobile } from '@/Composables/useMobile';

const props = defineProps({
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
  // Props reais vindas do Inertia
  protocolos: { type: Object, default: null },
  statistics: { type: Object, default: null },
  filters: { type: Object, default: null },
  statusOptions: { type: Object, default: null },
  analistas: { type: Array, default: null },
  empreendedores: { type: Array, default: null },
  empreendimentos: { type: Array, default: () => [] },
  canAtribuir: { type: Boolean, default: false },
  canCheck: { type: Boolean, default: false },
  canPdf: { type: Boolean, default: false },
  podeVerTodos: { type: Boolean, default: false },
});

// Detecção mobile
const { isMobile } = useMobile();

// Estado da visualização (mobile sempre será grade)
const viewMode = ref('table');

// ── Repositórios ─────────────────────────────────────────────
const mockRepository = new MockPaeProtocoloRepository();
const apiRepository = new ApiPaeProtocoloRepository();
const repository = props.useMock ? mockRepository : apiRepository;
const listUsecase = new ListPaeProtocolos(mockRepository);
const historicoUsecase = new GetPaeProtocoloHistorico(repository);
const { toast } = useToast();

const canAtribuirComputed = computed(() => props.useMock ? props.canEdit : props.canAtribuir);
const isExternalView = computed(() => (
  !props.canCreate &&
  !props.canEdit &&
  !props.canDelete &&
  !props.canExport &&
  !props.canAtribuir
));

const perPage = 15;
const currentPage = ref(1);
const mockFilters = ref({
  buscar: '',
  situacao: '',
  analista: '',
  empreendedor: '',
  data_inicio: '',
  data_fim: '',
});

const allProtocolos = ref([]);
if (props.useMock) {
  listUsecase.execute().then((rows) => {
    allProtocolos.value = rows;
  });
}

// ── Helpers para mapear dados reais ao shape esperado pelos componentes ──────
function normalizeDateBR(dateStr) {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  return d.toLocaleDateString('pt-BR');
}

function calcPrazo(limiteISO, situacao) {
  if (!limiteISO) return 'ok';
  const terminados = ['aprovado', 'ccpae', 'ativo_3_anos', 'reprovado', 'revogado'];
  if (terminados.includes(situacao)) return 'ok';
  const diff = (new Date(limiteISO) - new Date()) / (1000 * 60 * 60 * 24);
  if (diff < 0) return 'vencido';
  if (diff <= 10) return 'proximo';
  return 'ok';
}

function mapProtocolo(p) {
  const limiteISO = p.limite_analise ?? null;
  const situacao = p.status ?? p.situacao ?? '';
  return {
    id: p.id,
    protocoloNumero: p.num_protocolo ?? p.protocoloNumero ?? '',
    empreendedor: p.empreendimento?.empdor?.nome ?? p.empreendedor ?? 'N/A',
    estrutura: p.empreendimento?.nome ?? p.estrutura ?? '',
    analista: p.analista_atual?.name ?? p.analistaAtual?.name ?? p.analista ?? 'Não atribuído',
    situacao,
    dataEntrada: normalizeDateBR(p.dt_entrada ?? p.dataEntrada),
    limiteAnalise: normalizeDateBR(limiteISO ?? p.limiteAnalise),
    limiteAnaliseISO: limiteISO,
    prazo: calcPrazo(limiteISO, situacao),
    ccpae: !!p.ccpae,
  };
}

// ── Dados computados (real ou mock) ──────────────────────────
const situacoes = computed(() => {
  if (!props.useMock && props.statusOptions) {
    return [
      { value: '', label: 'Todas as situações' },
      ...Object.entries(props.statusOptions).map(([value, label]) => ({ value, label })),
    ];
  }
  return paeSituacoes;
});

const analistas = computed(() => {
  if (!props.useMock && props.analistas) return props.analistas;
  return paeAnalistas;
});

const empreendedores = computed(() => {
  if (!props.useMock && props.empreendedores) return props.empreendedores;
  return paeEmpreendedores;
});

const filters = computed(() => {
  if (props.useMock) return mockFilters.value;
  return props.filters ?? {};
});

const filteredProtocolos = computed(() => {
  if (props.useMock) {
    return (allProtocolos.value || []).filter((p) => matchesPaeFilters(p, mockFilters.value));
  }
  return (props.protocolos?.data ?? []).map(mapProtocolo);
});

const statsToUse = computed(() => {
  if (!props.useMock && props.statistics) {
    const s = props.statistics;
    return {
      total: s.total ?? 0,
      historico: (s.aprovado ?? 0) + (s.ccpae ?? 0) + (s.ativo_3_anos ?? 0),
      vencidos: s.vencidos ?? 0,
      ccpae: s.ccpae ?? 0,
    };
  }
  return getMockPaeStats(filteredProtocolos.value);
});

const paginationToUse = computed(() => {
  if (!props.useMock && props.protocolos) {
    return {
      current_page: props.protocolos.current_page,
      last_page: props.protocolos.last_page,
      per_page: props.protocolos.per_page,
      total: props.protocolos.total,
    };
  }
  const total = filteredProtocolos.value.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, currentPage.value), lastPage);
  return { current_page: safePage, last_page: lastPage, per_page: perPage, total };
});

const paginatedProtocolos = computed(() => {
  if (!props.useMock) return filteredProtocolos.value;
  const start = (paginationToUse.value.current_page - 1) * perPage;
  return filteredProtocolos.value.slice(start, start + perPage);
});

function handleFilterChange(next) {
  if (props.useMock) {
    mockFilters.value = { ...mockFilters.value, ...(next || {}) };
    currentPage.value = 1;
  } else {
    router.get(route('pae.protocolos.index'), { ...filters.value, ...(next || {}) }, { preserveState: true, replace: true });
  }
}

function handleFilterReset() {
  if (props.useMock) {
    mockFilters.value = { buscar: '', situacao: '', analista: '', empreendedor: '', data_inicio: '', data_fim: '' };
    currentPage.value = 1;
  } else {
    router.get(route('pae.protocolos.index'), {}, { preserveState: false });
  }
}

function handleCcpaeFilter() {
  handleFilterChange({ status: 'ccpae' });
}

function handleTotalProtocolos() {
  if (props.useMock) {
    mockFilters.value = { buscar: '', situacao: '', analista: '', empreendedor: '', data_inicio: '', data_fim: '' };
    currentPage.value = 1;
    return;
  }

  router.get(route('pae.protocolos.index'), {}, { preserveState: false, replace: true });
}

function handleHistoricoProtocolos() {
  if (props.useMock) {
    mockFilters.value = { ...mockFilters.value, situacao: 'historico' };
    currentPage.value = 1;
    return;
  }

  router.get(route('pae.protocolos.index'), { status_grupo: 'historico' }, { preserveState: false, replace: true });
}

function handleVencidosProtocolos() {
  if (props.useMock) {
    mockFilters.value = { ...mockFilters.value, situacao: 'vencido' };
    currentPage.value = 1;
    return;
  }

  router.get(route('pae.protocolos.index'), { status_grupo: 'vencidos' }, { preserveState: false, replace: true });
}

function handlePageChange(page) {
  if (props.useMock) {
    currentPage.value = page;
  } else {
    router.get(route('pae.protocolos.index'), { ...filters.value, page }, { preserveState: true, replace: true });
  }
}

function handleView(id) {
  router.visit(route('pae.index', { protocolo_id: id }));
}

function handleEdit(id) {
  router.visit(route('pae.index', { protocolo_id: id }));
}

// Modal de Confirmacao de Exclusao / Arquivamento
const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const protocoloIdToDelete = ref(null);
const showArchiveConfirm = ref(false);
const archiveLoading = ref(false);
const protocoloIdToArchive = ref(null);
const protocoloJaArquivado = ref(false);
const showCcpaeConfirm = ref(false);
const ccpaeLoading = ref(false);
const protocoloIdToCcpae = ref(null);

function handleArchive(id) {
  const protocolo = (filteredProtocolos.value || []).find((p) => p.id === id);
  if (!protocolo) return;

  protocoloIdToArchive.value = id;
  protocoloJaArquivado.value = Boolean(protocolo.arquivado);
  showArchiveConfirm.value = true;
}

function confirmArchive() {
  if (!protocoloIdToArchive.value) return;

  const acao = protocoloJaArquivado.value ? 'desarquivar' : 'arquivar';
  archiveLoading.value = true;

  router.patch(route(`pae.protocolos.${acao}`, protocoloIdToArchive.value), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showArchiveConfirm.value = false;
      protocoloIdToArchive.value = null;
      protocoloJaArquivado.value = false;
    },
    onError: () => {
      alert(`Erro ao ${acao} protocolo. Tente novamente.`);
    },
    onFinish: () => {
      archiveLoading.value = false;
    },
  });
}

function cancelArchive() {
  showArchiveConfirm.value = false;
  protocoloIdToArchive.value = null;
  protocoloJaArquivado.value = false;
}

function handleDelete(id) {
  const protocolo = (filteredProtocolos.value || []).find((p) => p.id === id);
  if (!protocolo) return;
  
  protocoloIdToDelete.value = id;
  showDeleteConfirm.value = true;
}

function confirmDelete() {
  if (protocoloIdToDelete.value) {
    deleteLoading.value = true;
    router.delete(route('pae.protocolos.destroy', protocoloIdToDelete.value), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteConfirm.value = false;
        protocoloIdToDelete.value = null;
      },
      onError: () => {
        alert('Erro ao excluir protocolo. Tente novamente.');
      },
      onFinish: () => {
        deleteLoading.value = false;
      },
    });
  }
}

function cancelDelete() {
  showDeleteConfirm.value = false;
  protocoloIdToDelete.value = null;
}

function handleOptions(_id) {
}

function handleCheck(id) {
  const protocolo = (filteredProtocolos.value || []).find((p) => p.id === id);
  if (!protocolo) return;

  protocoloIdToCcpae.value = id;
  showCcpaeConfirm.value = true;
}

function getRequestErrorMessage(error) {
  const data = error?.response?.data;
  const firstValidationMessage = data?.errors
    ? Object.values(data.errors).flat().find(Boolean)
    : null;

  return firstValidationMessage
    || data?.message
    || 'Erro ao concluir protocolo para CCPAE. Tente novamente.';
}

async function confirmCcpae() {
  if (!protocoloIdToCcpae.value) return;

  ccpaeLoading.value = true;

  try {
    const ax = window.axios || (await import('axios')).default;

    await ax.post(route('pae.protocolos.status', protocoloIdToCcpae.value), {
      novo_status: 'ccpae',
      obs: 'Protocolo concluido para CCPAE.',
    }, {
      headers: { Accept: 'application/json' },
    });

    showCcpaeConfirm.value = false;
    protocoloIdToCcpae.value = null;
    toast('Protocolo concluido para CCPAE.', 'success');
    router.get(route('pae.protocolos.index'), { ...filters.value, status: 'ccpae' }, { preserveState: false, replace: true });
  } catch (error) {
    toast(getRequestErrorMessage(error), 'error');
  } finally {
    ccpaeLoading.value = false;
  }
}

function cancelCcpae() {
  showCcpaeConfirm.value = false;
  protocoloIdToCcpae.value = null;
}

function handlePdf(id) {
  handlePrint(id);
}

// Modal de atribuicao
const showAssignModal = ref(false);
const selectedProtocoloAssign = ref(null);

function handleAssign(id) {
  const protocolo = (filteredProtocolos.value || []).find((p) => p.id === id);
  if (protocolo) {
    selectedProtocoloAssign.value = protocolo;
    showAssignModal.value = true;
  }
}

function closeAssignModal() {
  showAssignModal.value = false;
  setTimeout(() => {
    selectedProtocoloAssign.value = null;
  }, 300);
}

function handleAssignedAction() {
  // O Inertia redireciona e reflete o banco automaticamente com success.
}

// Modal de historico
const historicoModalOpen = ref(false);
const selectedProtocolo = ref(null);
const historicoPayload = ref(null);

async function handleHistory(id) {
  selectedProtocolo.value = (filteredProtocolos.value || []).find((p) => p.id === id) || null;
  historicoPayload.value = await historicoUsecase.execute(id);
  historicoModalOpen.value = true;
}

function closeHistorico() {
  historicoModalOpen.value = false;
  selectedProtocolo.value = null;
  historicoPayload.value = null;
}

// Modal de Impressão
const printModalOpen = ref(false);
const selectedProtocoloPrint = ref(null);

function handlePrint(id) {
  const protocolo = (filteredProtocolos.value || []).find((p) => p.id === id) || null;
  if (protocolo) {
    selectedProtocoloPrint.value = protocolo;
    printModalOpen.value = true;
  }
}

function closePrint() {
  printModalOpen.value = false;
  selectedProtocoloPrint.value = null;
}

// ── Novo Protocolo ─────────────────────────────────────
const novoForm = useForm({
    pae_empnto_id: '',
    sei_numero: '',
});

function openNovoProtocolo() {
    novoForm.post(route('pae.protocolos.store'), {
        onError: (errors) => {
            console.error('Validation errors:', errors);
            const { toast } = useToast();
            toast('Erro ao criar protocolo. Verifique os dados.', 'error');
        },
    });
}

// =========================
// Modal de Exportação CSV (Usando Composable)
// =========================
import { useExport } from '@/Composables/useExport';

const { 
  showExportModal, 
  handleExport: triggerExport 
} = useExport('pae.export');

function handleExportCsv(params) {
  // Passamos os filtros atuais da tela para serem combinados com os filtros do modal
  triggerExport(params, filters.value);
}
</script>

<style scoped>
.pae-protocolos-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>


