<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import AbrirPlantaoModal from '@/Components/Organisms/Plantao/AbrirPlantaoModal.vue';
import PlantaoFiltersSection from '@/Components/Organisms/Plantao/PlantaoFiltersSection.vue';
import PlantaoGrid from '@/Components/Organisms/Plantao/PlantaoGrid.vue';
import PlantaoStatsCards from '@/Components/Organisms/Plantao/PlantaoStatsCards.vue';
import PlantaoTable from '@/Components/Organisms/Plantao/PlantaoTable.vue';
import { useExport } from '@/Composables/useExport';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon, NewspaperIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  plantoes: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    required: true,
  },
  pagination: {
    type: Object,
    default: null,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
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

const emit = defineEmits(['view', 'edit', 'filter', 'abrir-plantao']);

const viewMode = ref('table');
const showAbrirModal = ref(false);
const { isMobile } = useMobile();

// Card de estatistica como filtro rapido: recebe o status ('' = Total, limpa o status)
// e preserva os demais filtros ativos (periodo, search).
const handleStatFilter = (status) => {
  emit('filter', { ...props.filters, status: status || undefined });
};

// Export Setup
const {
  showExportModal,
  handleExport: triggerExport
} = useExport('plantao.export');

const handleExportCsv = (params) => {
  triggerExport(params, props.filters);
};

const handleBuscarNoticias = () => {
  router.visit(route('plantao.noticias'));
};
</script>

<template>
  <div class="plantao-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Plantão Diário"
      description="Gestão de turnos e lançamentos operacionais"
      :icon="ClockIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
      icon-class="text-blue-600 dark:text-blue-400"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <Button
            variant="secondary"
            size="md"
            :icon="NewspaperIcon"
            icon-position="left"
            @click="handleBuscarNoticias"
          >
            <span class="hidden sm:inline">Buscar Noticias</span>
            <span class="sm:hidden">Noticias</span>
          </Button>

          <Button
            v-if="canExport"
            variant="success"
            size="md"
            :icon="ArrowDownTrayIcon"
            icon-position="left"
            @click="showExportModal = true"
          >
            <span class="hidden sm:inline">Exportar Excel</span>
            <span class="sm:hidden">Exp</span>
          </Button>

          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="showAbrirModal = true"
          >
            <span class="hidden sm:inline">Abrir Plantão</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Smart Cards -->
    <PlantaoStatsCards
      :statistics="statistics"
      class="mb-6"
      @filter="handleStatFilter"
    />

    <!-- Filtros -->
    <PlantaoFiltersSection
      :filters="filters"
      @filter-change="emit('filter', $event)"
      @filter-reset="emit('filter', {})"
    />

    <!-- Grid (Mobile ou Desktop selecionado) -->
    <PlantaoGrid
      v-if="viewMode === 'grid' || isMobile"
      :plantoes="plantoes"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="emit('view', $event)"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Tabela (Desktop selecionado) -->
    <PlantaoTable
      v-else-if="viewMode === 'table' && !isMobile"
      :plantoes="plantoes"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="emit('view', $event)"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => emit('filter', { page })"
      />
    </div>

    <!-- Modal: Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Plantão Diário"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Modal: Abrir Plantão -->
    <AbrirPlantaoModal
      :show="showAbrirModal"
      :periodos="filterOptions?.periodos"
      @close="showAbrirModal = false"
      @submit="(data) => { showAbrirModal = false; emit('abrir-plantao', data); }"
    />
  </div>
</template>

<style scoped>
.plantao-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
