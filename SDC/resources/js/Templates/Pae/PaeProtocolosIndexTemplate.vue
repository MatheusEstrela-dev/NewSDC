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

          <!-- Botão Novo Protocolo - Responsivo -->
          <Link :href="route('pae.index')">
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
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <PaeProtocolosTable
      v-else-if="viewMode === 'table' && !isMobile"
      :protocolos="paginatedProtocolos"
      @view="handleView"
      @print="handlePrint"
      @edit="handleEdit"
      @history="handleHistory"
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
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';

import PaeProtocolosStatsCards from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue';
import PaeProtocolosFilters from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosFilters.vue';
import PaeProtocolosGrid from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue';
import PaeProtocolosTable from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue';
import PaeHistoricoModal from '@/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue';
import PrintPaeProtocoloModal from '@/Components/Organisms/Pae/Print/PrintPaeProtocoloModal.vue';

import { MockPaeProtocoloRepository } from '@/infrastructure/pae/MockPaeProtocoloRepository';
import { ListPaeProtocolos } from '@/domain/pae/usecases/ListPaeProtocolos';
import { GetPaeProtocoloHistorico } from '@/domain/pae/usecases/GetPaeProtocoloHistorico';

import {
  paeSituacoes,
  paeAnalistas,
  paeEmpreendedores,
  getMockPaeStats,
  matchesPaeFilters,
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
});

// Detecção mobile
const { isMobile } = useMobile();

// Estado da visualização (mobile sempre será grade)
const viewMode = ref('grid');

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
  // TODO: quando existir edição por protocolo, trocar para rota correta.
  router.visit(route('pae.index'));
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

// #region agent log
function handlePrint(id) {
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'PaeProtocolosIndexTemplate.vue:handlePrint',message:'handlePrint called',data:{id,printModalOpenBefore:printModalOpen.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
  console.log('PAE: handlePrint called for id:', id);
  const protocolo = (allProtocolos.value || []).find((p) => p.id === id) || null;
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'PaeProtocolosIndexTemplate.vue:handlePrint',message:'Protocolo search result',data:{id,protocoloFound:!!protocolo,protocoloNumero:protocolo?.protocoloNumero},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
  console.log('PAE: Protocolo found:', protocolo);
  if (protocolo) {
    selectedProtocoloPrint.value = protocolo;
    printModalOpen.value = true;
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'PaeProtocolosIndexTemplate.vue:handlePrint',message:'State updated',data:{printModalOpen:printModalOpen.value,selectedProtocoloPrint:!!selectedProtocoloPrint.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
    console.log('PAE: printModalOpen set to true');
  } else {
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'PaeProtocolosIndexTemplate.vue:handlePrint',message:'Protocolo not found',data:{id},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
    console.warn('PAE: Protocolo not found for id:', id);
  }
}
// #endregion

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


