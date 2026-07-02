<template>
  <div class="inventario-container">
    <PageHeader
      title="Inventario"
      description="Controle inicial de equipamentos, patrimonio e emprestimos"
      :icon="ArchiveBoxIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <ViewModeToggle v-model="viewMode" />
          <ActionButton
            module="inventario"
            resource="equipamentos"
            action="create"
            :allowed="canCreate"
            size="md"
            label="Novo Equipamento"
            @click="emit('create')"
          >
            <span class="hidden sm:inline">Novo Equipamento</span>
            <span class="sm:hidden">Novo</span>
          </ActionButton>
        </div>
      </template>
    </PageHeader>

    <InventarioStatsCards
      :statistics="statistics"
      :loading="loading"
      @filter="handleStatFilter"
    />

    <InventarioFiltersSection
      v-model:filters="localFilters"
      :filter-options="filterOptions"
      @apply="handleApplyFilters"
      @clear="handleClearFilters"
    />

    <InventarioGrid
      v-if="viewMode === 'grid' || isMobile"
      :equipamentos="equipamentos"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <InventarioTable
      v-else
      :equipamentos="equipamentos"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import InventarioFiltersSection from '@/Components/Organisms/Inventario/InventarioFiltersSection.vue';
import InventarioGrid from '@/Components/Organisms/Inventario/InventarioGrid.vue';
import InventarioStatsCards from '@/Components/Organisms/Inventario/InventarioStatsCards.vue';
import InventarioTable from '@/Components/Organisms/Inventario/InventarioTable.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import { useMobile } from '@/Composables/useMobile';
import { ArchiveBoxIcon } from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';

const props = defineProps({
  equipamentos: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      disponiveis: 0,
      emprestados: 0,
      manutencao: 0,
      baixados: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
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
});

const emit = defineEmits(['filter-change', 'clear-filters', 'create', 'edit', 'delete']);

const { isMobile } = useMobile();
const viewMode = ref('table');
const localFilters = ref({ ...props.filters });

watch(
  () => props.filters,
  (filters) => {
    localFilters.value = { ...filters };
  },
  { deep: true }
);

function handleApplyFilters(filters) {
  emit('filter-change', filters);
}

function handleClearFilters() {
  localFilters.value = {};
  emit('clear-filters');
}

function handleStatFilter(situacao) {
  localFilters.value = {
    ...localFilters.value,
    situacao: situacao || '',
  };
  handleApplyFilters(localFilters.value);
}
</script>

<style scoped>
.inventario-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
