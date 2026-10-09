<template>
  <div class="pae-protocolos-container">
    <!-- Header Padronizado com Toggle -->
    <PageHeader
      title="Protocolos PAE"
      description="Gerencie os protocolos de análise de PAE"
      :icon="ClipboardDocumentListIcon"
      :icon-image="moduleIcon('pae')"
      variant="gradient"
    >
      <template #actions>
        <!-- flex-wrap aqui tambem: o PageHeader ja envolve o slot num container
             com wrap, mas se os botoes vierem num unico flex item sem wrap o
             container externo nao tem onde quebrar e o card (overflow-hidden)
             corta o ultimo botao entre md e ~1300px. -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botao CCPAE - filtro rapido -->
          <Button variant="violet" size="md" @click="handleCcpaeFilter">
            CCPAE
          </Button>

          <!-- Botao Arquivados - filtro rapido -->
          <Button variant="warning" size="md" :icon="ArchiveBoxIcon" icon-position="left" @click="handleArquivadosFilter">
            <span class="hidden sm:inline">Arquivados</span>
          </Button>

          <!-- Botao Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botao Novo Protocolo - Responsivo -->
          <Button v-if="canCreate" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="openNovoProtocolo">
            <span>Novo Protocolo</span>
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
      @ciclos="handleCiclosEsgotados"
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
      :can-archive="canArchive"
      :can-atribuir="canAtribuirComputed"
      :can-check="canCheck"
      :can-pdf="canPdf"
      :can-create="canCreate"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @check="handleCheck"
      @pdf="handlePdf"
      @ficha="handleFicha"
      @dco="handleDco"
      @evacuacao="handleEvacuacao"
      @archive="handleArchive"
      @delete="handleDelete"
      @options="handleOptions"
      @assign="handleAssign"
      @relate="handleRelate"
    />

    <!-- Desktop: Tabela (somente quando selecionada e nao mobile) -->
    <PaeProtocolosTable
      v-else-if="viewMode === 'table' && !isMobile"
      :protocolos="paginatedProtocolos"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-archive="canArchive"
      :can-atribuir="canAtribuirComputed"
      :can-check="canCheck"
      :can-pdf="canPdf"
      :can-create="canCreate"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @check="handleCheck"
      @pdf="handlePdf"
      @ficha="handleFicha"
      @dco="handleDco"
      @evacuacao="handleEvacuacao"
      @archive="handleArchive"
      @delete="handleDelete"
      @options="handleOptions"
      @assign="handleAssign"
      @relate="handleRelate"
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
      :can-edit="canEdit"
      :initial-tab="historicoInitialTab"
      @close="closeHistorico"
      @atualizado="recarregarHistorico"
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

    <EmitirCcpaeModal
      :show="showCcpaeModal"
      :protocolo="protocoloCcpae"
      @close="fecharCcpae"
    />
  </div>
</template>

<script setup>
import { ArchiveBoxIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useToast } from '@/Composables/useToast';

import Button from '@/Components/Atoms/Button/Button.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';

import PrintPaeProtocoloModal from '@/Components/Organisms/Pae/Print/PrintPaeProtocoloModal.vue';
import PaeHistoricoModal from '@/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue';
import PaeProtocolosFilters from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosFilters.vue';
import PaeProtocolosGrid from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue';
import PaeProtocolosStatsCards from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue';
import PaeProtocolosTable from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue';
import AssignAnalistaModal from '@/Components/Organisms/Pae/Protocolos/AssignAnalistaModal.vue';
import EmitirCcpaeModal from '@/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue';

import { GetPaeProtocoloHistorico } from '@/domain/pae/usecases/GetPaeProtocoloHistorico';
import { ApiPaeProtocoloRepository } from '@/infrastructure/pae/ApiPaeProtocoloRepository';

import { useMobile } from '@/Composables/useMobile';

const props = defineProps({
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
  canArchive: {
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

// Repositorio e dados da listagem enviados pelo servidor
const historicoUsecase = new GetPaeProtocoloHistorico(new ApiPaeProtocoloRepository());
const { toast } = useToast();

const canAtribuirComputed = computed(() => props.canAtribuir);
const isExternalView = computed(() => (
  !props.canCreate &&
  !props.canEdit &&
  !props.canDelete &&
  !props.canExport &&
  !props.canAtribuir
));

function normalizeDateBR(dateStr) {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
}

function mapProtocolo(p) {
  const limiteISO = p.limite_analise ?? null;
  const arquivado = !!p.arquivado;
  return {
    id: p.id,
    protocoloNumero: p.num_protocolo ?? '',
    empreendedor: p.empreendimento?.empdor?.nome ?? 'N/A',
    estrutura: p.empreendimento?.nome ?? '',
    analista: p.analista_atual?.name ?? 'Não atribuído',
    analista_atual_id: p.analista_atual_id ?? null,
    situacao: arquivado ? 'arquivado' : (p.status ?? ''),
    dataEntrada: normalizeDateBR(p.dt_entrada),
    limiteAnalise: normalizeDateBR(limiteISO),
    limiteAnaliseISO: limiteISO,
    prazo: p.prazo_situacao ?? 'ok',
    prazoEstimado: !!p.dt_notificacao_feam_estimada,
    foraDoPrazo: !!p.fora_do_prazo,
    ccpae: !!p.ccpae,
    comunicacoesPendentes: Number(p.comunicacoes_pendentes_count || 0),
    correcaoPrazoVencido: !!p.correcao_prazo_vencido,
    dcoSituacao: p.dco_situacao ?? null,
    dcoEmissaoPronta: !!p.dco_emissao_pronta,
    evacuacaoSituacao: p.evacuacao_situacao ?? null,
    arquivado,
  };
}

const situacoes = computed(() => [
  { value: '', label: 'Todas as situações' },
  ...Object.entries(props.statusOptions ?? {}).map(([value, label]) => ({ value, label })),
]);
const analistas = computed(() => props.analistas ?? []);
const empreendedores = computed(() => props.empreendedores ?? []);
const filters = computed(() => props.filters ?? {});
const filteredProtocolos = computed(() => (props.protocolos?.data ?? []).map(mapProtocolo));
const paginatedProtocolos = filteredProtocolos;

const statsToUse = computed(() => {
  const s = props.statistics ?? {};
  return {
    total: s.total ?? 0,
    historico: (s.aprovado ?? 0) + (s.ccpae ?? 0) + (s.ativo_3_anos ?? 0)
      + (s.reprovado ?? 0) + (s.reprovado_sumariamente ?? 0),
    vencidos: s.vencidos ?? 0,
    ciclos_esgotados: s.ciclos_esgotados ?? 0,
    ccpae: s.ccpae ?? 0,
  };
});

const paginationToUse = computed(() => (props.protocolos
  ? {
      current_page: props.protocolos.current_page,
      last_page: props.protocolos.last_page,
      per_page: props.protocolos.per_page,
      total: props.protocolos.total,
    }
  : null));

function visitar(params, preserveState = false) {
  router.get(route('pae.protocolos.index'), params, { preserveState, replace: true });
}

function handleFilterChange(next) {
  visitar({ ...filters.value, ...(next || {}) }, true);
}

function handleFilterReset() {
  visitar({});
}

function handleCcpaeFilter() {
  visitar({ status: 'ccpae' });
}

function handleArquivadosFilter() {
  visitar({ arquivado: 1 });
}

function handleTotalProtocolos() {
  visitar({});
}

function handleHistoricoProtocolos() {
  visitar({ status_grupo: 'historico' });
}

function handleVencidosProtocolos() {
  visitar({ status_grupo: 'vencidos' });
}

function handleCiclosEsgotados() {
  visitar({ status_grupo: 'ciclos_esgotados' });
}

function handlePageChange(page) {
  visitar({ ...filters.value, page }, true);
}
function handleView(id) {
  router.visit(route('pae.index', { protocolo_id: id, readonly: 1 }));
}

function handleEdit(id) {
  router.visit(route('pae.index', { protocolo_id: id }));
}

function handleFicha(id) {
  router.visit(route('pae.protocolo.ficha-anexo-b.show', id));
}

function handleDco(id) {
  router.visit(route('pae.protocolo.dco.show', id));
}

function handleEvacuacao(id) {
  router.visit(route('pae.protocolo.evacuacao.show', id));
}

function handleRelate(id) {
  if (!confirm('Criar nova versao relacionada deste protocolo?')) return;
  router.post(route('pae.protocolo.relacionar', id));
}

// Modal de Confirmacao de Exclusao / Arquivamento
const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const protocoloIdToDelete = ref(null);
const showArchiveConfirm = ref(false);
const archiveLoading = ref(false);
const protocoloIdToArchive = ref(null);
const protocoloJaArquivado = ref(false);
const showCcpaeModal = ref(false);
const protocoloCcpae = ref(null);

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
  protocoloCcpae.value = (filteredProtocolos.value || []).find((p) => p.id === id) || null;
  showCcpaeModal.value = !!protocoloCcpae.value;
}

function fecharCcpae() {
  showCcpaeModal.value = false;
  protocoloCcpae.value = null;
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
const historicoInitialTab = ref(null);

async function handleHistory(id, initialTab = null) {
  try {
    historicoPayload.value = await historicoUsecase.execute(id);
    selectedProtocolo.value = (filteredProtocolos.value || []).find((p) => p.id === id)
      || { id, protocoloNumero: historicoPayload.value?.protocolo ?? '' };
    historicoInitialTab.value = initialTab;
    historicoModalOpen.value = true;
  } catch {
    toast('Não foi possível abrir o histórico do protocolo.', 'error');
  }
}

onMounted(() => {
  const parametro = new URLSearchParams(window.location.search).get('triagem');
  const id = Number(parametro);
  if (parametro && /^\d+$/.test(parametro) && Number.isSafeInteger(id) && id > 0) {
    void handleHistory(id, 'admissibilidade');
  }
});

async function recarregarHistorico() {
  if (!selectedProtocolo.value) return;
  historicoPayload.value = await historicoUsecase.execute(selectedProtocolo.value.id);
}

function closeHistorico() {
  historicoModalOpen.value = false;
  historicoInitialTab.value = null;
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


