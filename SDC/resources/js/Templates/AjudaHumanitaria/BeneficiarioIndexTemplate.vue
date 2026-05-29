<template>
  <div class="beneficiarios-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Beneficiários"
      description="Gestão de beneficiários e famílias afetadas por desastres"
      :icon="HeartIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botão Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botão Criar - Responsivo -->
          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="$emit('create')"
          >
            <span class="hidden sm:inline">Novo Beneficiário</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Beneficiários"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Modal de Impressão do Beneficiário -->
    <PrintBeneficiarioModal
      :show="showPrintModal"
      :beneficiario="selectedBeneficiario"
      :loading="printLoading"
      @close="showPrintModal = false"
    />

    <!-- Statistics Cards -->
    <BeneficiarioStatsCards
      :statistics="statistics"
      @filter="handleStatFilter"
    />

    <!-- Filters -->
    <BeneficiarioFiltersSection
      :filters="localFilters"
      :municipalities="municipalities"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />

    <!-- Mobile: Sempre Grade | Desktop: Grade ou Tabela -->
    <BeneficiarioGrid
      v-if="viewMode === 'grid' || isMobile"
      :beneficiarios="beneficiarios"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @print="handlePrint"
      @edit="(id) => $emit('edit', id)"
      @delete="(id) => $emit('delete', id)"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <div v-else-if="viewMode === 'table' && !isMobile" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Nome</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">CPF</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Contato</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider w-40 min-w-40">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="beneficiario in beneficiarios" :key="beneficiario.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900 dark:text-white">{{ beneficiario.nome }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.cpf || '—' }}</td>
            <td class="px-4 py-3">
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                beneficiario.status === 'ATIVO' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                beneficiario.status === 'EM_ABRIGO' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300'
              ]">
                {{ beneficiario.status?.replace('_', ' ') || 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.telefone || '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.municipio?.nome || beneficiario.municipio || '—' }}</td>
            <td class="px-4 py-3 text-right w-40 min-w-40">
              <div class="flex justify-end">
                <ActionButton
                  module="humanitaria"
                  resource="beneficiarios"
                  size="sm"
                  :actions="[
                    { action: 'view',   handler: () => $emit('view', beneficiario.id) },
                    { action: 'print',  handler: () => handlePrint(beneficiario.id) },
                    { action: 'edit',   handler: () => $emit('edit', beneficiario.id),   allowed: canEdit },
                    { action: 'delete', handler: () => $emit('delete', beneficiario.id), allowed: canDelete },
                  ]"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!beneficiarios || beneficiarios.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Nenhum beneficiário encontrado
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => emit('filter', { page })"
      />
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import BeneficiarioFiltersSection from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioFiltersSection.vue';
import BeneficiarioGrid from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioGrid.vue';
import BeneficiarioStatsCards from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioStatsCards.vue';
import PrintBeneficiarioModal from '@/Components/Organisms/AjudaHumanitaria/Print/PrintBeneficiarioModal.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useExport } from '@/Composables/useExport';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
  beneficiarios: {
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
  loading: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canCreate: {
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
  filters: {
    type: Object,
    default: () => ({}),
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'print', 'filter', 'filter-change', 'filter-reset']);

// Detecção mobile
const { isMobile } = useMobile();

const viewMode = ref('table');
const localFilters = ref({ ...props.filters });

const handleStatFilter = (filter) => {
  emit('filter', filter);
};

const handleFilterChange = (newFilters) => {
  localFilters.value = { ...newFilters };
  emit('filter-change', newFilters);
};

const handleFilterReset = () => {
  localFilters.value = {};
  emit('filter-reset');
};

// =========================
// Modal de Exportação CSV (Usando Composable)
// =========================
const { 
  showExportModal, 
  handleExport: triggerExport 
} = useExport('ajuda-humanitaria.beneficiarios.export');

function handleExportCsv(params) {
  triggerExport(params, localFilters.value);
}

// =========================
// Modal de Impressão
// =========================
const showPrintModal = ref(false);
const selectedBeneficiario = ref(null);
const printLoading = ref(false);

function handlePrint(id) {
  // Encontrar beneficiário na lista
  const beneficiario = props.beneficiarios.find(b => b.id === id);
  if (beneficiario) {
    selectedBeneficiario.value = beneficiario;
    showPrintModal.value = true;
  }
}
</script>

<style scoped>
.beneficiarios-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
