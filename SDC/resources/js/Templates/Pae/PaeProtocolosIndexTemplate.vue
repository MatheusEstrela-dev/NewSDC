<template>
  <div class="pae-protocolos-container">
    <PaeProtocolosHeader />

    <PaeProtocolosStatsCards :stats="statsToUse" />

    <PaeProtocolosFilters
      :filters="filters"
      :situacoes="situacoes"
      :analistas="analistas"
      :empreendedores="empreendedores"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />

    <PaeProtocolosGrid
      :protocolos="paginatedProtocolos"
      :loading="loading"
      :pagination="paginationToUse"
      @view="handleView"
      @edit="handleEdit"
      @history="handleHistory"
      @page-change="handlePageChange"
    />

    <PaeHistoricoModal
      :open="historicoModalOpen"
      :protocolo="selectedProtocolo"
      :historico="historicoPayload"
      @close="closeHistorico"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

import PaeProtocolosHeader from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosHeader.vue';
import PaeProtocolosStatsCards from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue';
import PaeProtocolosFilters from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosFilters.vue';
import PaeProtocolosGrid from '@/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue';
import PaeHistoricoModal from '@/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue';

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

// Data source (mock por enquanto)
const repository = new MockPaeProtocoloRepository();
const listUsecase = new ListPaeProtocolos(repository);
const historicoUsecase = new GetPaeProtocoloHistorico(repository);

// Local state (modo mock: tudo local)
const perPage = 12;
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
</script>

<style scoped>
.pae-protocolos-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .pae-protocolos-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .pae-protocolos-container {
    padding: 2rem 2.5rem;
  }
}
</style>


