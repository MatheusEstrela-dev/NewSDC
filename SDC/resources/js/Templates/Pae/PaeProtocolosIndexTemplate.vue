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

          <!-- Botao Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botao Novo Protocolo - Responsivo -->
          <Link v-if="canCreate" :href="route('pae.index')">
            <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
              <span class="hidden sm:inline">Novo Protocolo</span>
              <span class="sm:hidden">Novo</span>
            </Button>
          </Link>
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

    <PaeProtocolosStatsCards :stats="statsToUse" />

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
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @archive="handleArchive"
    />

    <!-- Desktop: Tabela (somente quando selecionada e nao mobile) -->
    <PaeProtocolosTable
      v-else-if="viewMode === 'table' && !isMobile"
      :protocolos="paginatedProtocolos"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
      @archive="handleArchive"
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
      @close="closeHistorico"
    />

    <PrintPaeProtocoloModal
      :show="printModalOpen"
      :protocolo="selectedProtocoloPrint"
      @close="closePrint"
    />
  </div>
</template>

<script setup>
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Button from '@/Components/Atoms/Button/Button.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';

import PrintPaeProtocoloModal from '@/Components/Organisms/Pae/Print/PrintPaeProtocoloModal.vue';
import PaeHistoricoModal from '@/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue';
import PaeProtocolosFilters from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosFilters.vue';
import PaeProtocolosGrid from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue';
import PaeProtocolosStatsCards from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue';
import PaeProtocolosTable from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue';

import { GetPaeProtocoloHistorico } from '@/domain/pae/usecases/GetPaeProtocoloHistorico';
import { ListPaeProtocolos } from '@/domain/pae/usecases/ListPaeProtocolos';
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
});

// Detecção mobile
const { isMobile } = useMobile();

// Estado da visualização (mobile sempre será grade)
const viewMode = ref('table');

// Data source (mock por enquanto)
const repository = new MockPaeProtocoloRepository();
const listUsecase = new ListPaeProtocolos(repository);
const historicoUsecase = new GetPaeProtocoloHistorico(repository);

// Local state (modo mock: tudo local)
const perPage = 15;
const currentPage = ref(1);
const filters = ref({
  buscar: '',
  situacao: '',
  analista: '',
  empreendedor: '',
  data_inicio: '',
  data_fim: '',
});

// Dados carregados (mock síncrono via usecase async; iniciamos com vazio e preenchemos)
const allProtocolos = ref([]);
listUsecase.execute().then((rows) => {
  allProtocolos.value = rows;
});

const situacoes = paeSituacoes;
const analistas = paeAnalistas;
const empreendedores = paeEmpreendedores;

const filteredProtocolos = computed(() => {
  return (allProtocolos.value || []).filter((p) => matchesPaeFilters(p, filters.value));
});

const statsToUse = computed(() => getMockPaeStats(filteredProtocolos.value));

const paginationToUse = computed(() => {
  const total = filteredProtocolos.value.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, currentPage.value), lastPage);

  return {
    current_page: safePage,
    last_page: lastPage,
    per_page: perPage,
    total,
  };
});

const paginatedProtocolos = computed(() => {
  const start = (paginationToUse.value.current_page - 1) * perPage;
  const end = start + perPage;
  return filteredProtocolos.value.slice(start, end);
});

function handleFilterChange(next) {
  filters.value = { ...filters.value, ...(next || {}) };
  currentPage.value = 1;
}

function handleFilterReset() {
  filters.value = {
    buscar: '',
    situacao: '',
    analista: '',
    empreendedor: '',
    data_inicio: '',
    data_fim: '',
  };
  currentPage.value = 1;
}

function handlePageChange(page) {
  currentPage.value = page;
}

function handleView(id) {
  // TODO: quando existir página real de detalhes do protocolo PAE, trocar para rota correta.
  router.visit(route('pae.index'));
}

function handleEdit(id) {
  // TODO: quando existir edicao por protocolo, trocar para rota correta.
  router.visit(route('pae.index'));
}

async function handleArchive(id) {
  const protocolo = (allProtocolos.value || []).find((p) => p.id === id);
  if (!protocolo) return;

  const confirmed = confirm(
    `Deseja arquivar o protocolo #${protocolo.protocoloNumero}?\n\nEsta acao pode ser revertida posteriormente.`
  );

  if (!confirmed) return;

  try {
    // TODO: Implementar chamada real a API quando disponivel
    // await router.delete(route('api.pae.protocolos.archive', id));

    // Por enquanto, remove localmente do array (mock)
    const index = allProtocolos.value.findIndex((p) => p.id === id);
    if (index !== -1) {
      allProtocolos.value.splice(index, 1);
    }

    alert('Protocolo arquivado com sucesso!');
  } catch (error) {
    alert('Erro ao arquivar protocolo. Tente novamente.');
  }
}

// Modal de histórico
const historicoModalOpen = ref(false);
const selectedProtocolo = ref(null);
const historicoPayload = ref(null);

async function handleHistory(id) {
  selectedProtocolo.value = (allProtocolos.value || []).find((p) => p.id === id) || null;
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
  const protocolo = (allProtocolos.value || []).find((p) => p.id === id) || null;
  if (protocolo) {
    selectedProtocoloPrint.value = protocolo;
    printModalOpen.value = true;
  }
}

function closePrint() {
  printModalOpen.value = false;
  selectedProtocoloPrint.value = null;
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


