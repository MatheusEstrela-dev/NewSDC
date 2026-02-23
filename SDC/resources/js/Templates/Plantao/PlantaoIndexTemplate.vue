<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AbrirPlantaoModal from '@/Components/Organisms/Plantao/AbrirPlantaoModal.vue';
import PlantaoGrid from '@/Components/Organisms/Plantao/PlantaoGrid.vue'; // New import
import PlantaoStatsCards from '@/Components/Organisms/Plantao/PlantaoStatsCards.vue';
import PlantaoTable from '@/Components/Organisms/Plantao/PlantaoTable.vue';
import { useExport } from '@/Composables/useExport';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
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

const viewMode = ref('grid');
const showAbrirModal = ref(false);

const handleStatFilter = (statId) => {
  const statusMap = {
    total: null,
    ativos: 'ATIVO',
    finalizados_hoje: 'FINALIZADO',
    equipe_online: null,
  };
  emit('filter', { status: statusMap[statId] });
};

// Export Setup
const {
  showExportModal,
  handleExport: triggerExport
} = useExport('plantao.export');

const handleExportCsv = (params) => {
  triggerExport(params, props.filters);
};
</script>

<template>
  <div class="plantao-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Plantão Diário"
      description="Gestão de turnos e lançamentos operacionais"
      :icon="ClockIcon"
      variant="gradient"
      icon-class="text-blue-600 dark:text-blue-400"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

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
