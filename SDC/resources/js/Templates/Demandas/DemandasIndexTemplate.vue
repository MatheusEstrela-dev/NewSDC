<template>
  <div class="demandas-container">
    <DemandasPageHeader @open-modal="showModal = true" />
    <DemandasStatisticsCards :statistics="demandasStatistics" />

    <DemandasList
      :demandas="demandas"
      :filters="filters"
      :current-page="currentPage"
      :total-pages="totalPages"
      :get-tipo-label="getTipoLabel"
      :get-prioridade-label="getPrioridadeLabel"
      :get-status-label="getStatusLabel"
      @filter-change="handleFilterChange"
      @clear-filters="handleClearFilters"
      @page-change="handlePageChange"
      @demanda-click="handleDemandaClick"
    />

    <NovaDemandaModal
      :show="showModal"
      @close="showModal = false"
      @submit="handleCreateDemanda"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useDemandas } from '@/Composables/useDemandas';
import DemandasPageHeader from '@/Components/Organisms/Demandas/Header/DemandasPageHeader.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import DemandasList from '@/Components/Organisms/Demandas/Lists/DemandasList.vue';
import NovaDemandaModal from '@/Components/Organisms/Demandas/Modals/NovaDemandaModal.vue';

const props = defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      abertas: 0,
      em_andamento: 0,
      concluidas: 0,
    }),
  },
});

// Usa o composable de demandas
const {
  demandas,
  filters,
  currentPage,
  totalPages,
  statistics: demandasStatistics,
  createDemanda,
  setFilters,
  clearFilters,
  goToPage,
  getTipoLabel,
  getPrioridadeLabel,
  getStatusLabel,
} = useDemandas();

const showModal = ref(false);

const handleFilterChange = (newFilters) => {
  setFilters(newFilters);
};

const handleClearFilters = () => {
  clearFilters();
};

const handlePageChange = (page) => {
  goToPage(page);
};

const handleDemandaClick = (demanda) => {
  console.log('Demanda clicada:', demanda);
  // Futuramente: abrir modal de detalhes ou navegar para página de detalhes
};

const handleCreateDemanda = (demandaData) => {
  const newDemanda = createDemanda(demandaData);
  showModal.value = false;
  console.log('Nova demanda criada:', newDemanda);
  // Futuramente: mostrar toast de sucesso
};
</script>

<style scoped>
.demandas-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .demandas-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .demandas-container {
    padding: 2rem 2.5rem;
  }
}
</style>
