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

    <RatStatisticsCards :statistics="statisticsToUse" />
    <RatFiltersSection
      :filters="filtersToUse"
      :municipalities="municipalities"
      :cobrade-types="cobradeTypes"
      :years="years"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />
    <RatTable
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
import RatStatisticsCards from '../../Components/Organisms/Rat/Statistics/RatStatisticsCards.vue';
import RatFiltersSection from '../../Components/Organisms/Rat/Filters/RatFiltersSection.vue';
import RatTable from '../../Components/Organisms/Rat/Table/RatTable.vue';
import PrintBoletimModal from '../../Components/Organisms/Rat/Print/PrintBoletimModal.vue';
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

// #region agent log
async function handlePrint(id) {
  const logData = {location:'RatIndexTemplate.vue:handlePrint',message:'handlePrint called',data:{id,showPrintModalBefore:showPrintModal.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'};
  console.log('DEBUG:', logData);
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData)}).catch(()=>{});
  console.log('RAT: handlePrint called for id:', id);
  showPrintModal.value = true;
  printLoading.value = true;
  selectedOcorrencia.value = null;
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'RatIndexTemplate.vue:handlePrint',message:'State updated before fetch',data:{showPrintModal:showPrintModal.value,printLoading:printLoading.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});

  try {
    const url = route('rat.show.json', id);
    const logData1 = {location:'RatIndexTemplate.vue:handlePrint',message:'Fetching data',data:{url,id},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'};
    console.log('DEBUG:', logData1);
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData1)}).catch(()=>{});
    console.log('RAT: Fetching URL:', url);
    const response = await fetch(url);
    const logData2 = {location:'RatIndexTemplate.vue:handlePrint',message:'Response received',data:{status:response.status,statusText:response.statusText,ok:response.ok,headers:Object.fromEntries(response.headers.entries())},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'};
    console.log('DEBUG:', logData2);
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData2)}).catch(()=>{});
    if (!response.ok) {
      const errorText = await response.text();
      const logData3 = {location:'RatIndexTemplate.vue:handlePrint',message:'Response not OK',data:{status:response.status,errorText},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'};
      console.error('DEBUG:', logData3);
      fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData3)}).catch(()=>{});
      throw new Error(`Erro ao carregar dados: ${response.status} ${response.statusText}`);
    }
    const data = await response.json();
    const logData4 = {location:'RatIndexTemplate.vue:handlePrint',message:'Data parsed from JSON',data:{hasData:!!data,dataKeys:data?Object.keys(data):[],hasNumeroBos:!!data?.numero_bos,hasDadosGerais:!!data?.dados_gerais,hasEnvolvidos:!!data?.envolvidos,hasRecursos:!!data?.recursos,hasVistoria:!!data?.vistoria,hasHistorico:!!data?.historico},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'};
    console.log('DEBUG:', logData4);
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData4)}).catch(()=>{});
    console.log('RAT: Data loaded:', data);
    selectedOcorrencia.value = data;
    const logData5 = {location:'RatIndexTemplate.vue:handlePrint',message:'State updated after data load',data:{showPrintModal:showPrintModal.value,hasOcorrencia:!!selectedOcorrencia.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'};
    console.log('DEBUG:', logData5);
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData5)}).catch(()=>{});
  } catch (error) {
    const logData6 = {location:'RatIndexTemplate.vue:handlePrint',message:'Error loading data',data:{error:error.message,errorStack:error.stack,showPrintModal:showPrintModal.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'};
    console.error('DEBUG:', logData6);
    fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData6)}).catch(()=>{});
    console.error('RAT: Erro ao carregar ocorrencia:', error);
    alert('Erro ao carregar dados do boletim: ' + error.message);
    showPrintModal.value = false;
  } finally {
    printLoading.value = false;
  }
}
// #endregion

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
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .rat-index-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .rat-index-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .rat-index-container {
    padding: 2rem 3rem;
  }
}
</style>

