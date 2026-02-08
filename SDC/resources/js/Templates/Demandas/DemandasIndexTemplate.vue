<template>
  <div class="demandas-container">
    <DemandasPageHeader @open-modal="showModal = true" @open-export="showExportModal = true" />
    <DemandasStatisticsCards :statistics="demandasStatistics" />

    <DemandasList
      :demandas="demandas"
      :filters="filters"
      :get-tipo-label="getTipoLabel"
      :get-prioridade-label="getPrioridadeLabel"
      :get-status-label="getStatusLabel"
      @filter-change="handleFilterChange"
      @clear-filters="handleClearFilters"
      @demanda-click="handleDemandaClick"
    />

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="handlePageChange"
      />
    </div>

    <NovaDemandaModal
      :show="showModal"
      @close="showModal = false"
      @submit="handleCreateDemanda"
    />

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Demandas"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useDemandas } from '@/Composables/useDemandas';
import { useExport } from '@/Composables/useExport';
import DemandasPageHeader from '@/Components/Organisms/Demandas/Header/DemandasPageHeader.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import DemandasList from '@/Components/Organisms/Demandas/Lists/DemandasList.vue';
import NovaDemandaModal from '@/Components/Organisms/Demandas/Modals/NovaDemandaModal.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';

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
  pagination,
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

const { 
  showExportModal, 
  handleExport: triggerExport 
} = useExport('admin.demandas.export');

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

function handleExportCsv(params) {
  triggerExport(params, filters.value);
}
</script>

<style scoped>
.demandas-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
