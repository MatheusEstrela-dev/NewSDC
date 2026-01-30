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

          <!-- Botão Criar - Responsivo -->
          <Button
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
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
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
            <td class="px-4 py-3 text-right">
              <TableActions
                :show-view="true"
                :show-print="true"
                :show-edit="canEdit"
                :show-attachments="false"
                :show-delete="canDelete"
                size="sm"
                @view="$emit('view', beneficiario.id)"
                @print="handlePrint(beneficiario.id)"
                @edit="$emit('edit', beneficiario.id)"
                @delete="$emit('delete', beneficiario.id)"
              />
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
    <div v-if="pagination && pagination.last_page > 1" class="mt-6">
      <div class="flex items-center justify-between px-6 py-4 bg-white dark:bg-slate-900/60 rounded-lg border border-slate-200 dark:border-slate-700/30">
        <p class="text-sm text-slate-600 dark:text-slate-400">
          Mostrando {{ pagination.from || 0 }} a {{ pagination.to || 0 }} de {{ pagination.total || 0 }} resultados
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import BeneficiarioStatsCards from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioStatsCards.vue';
import BeneficiarioFiltersSection from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioFiltersSection.vue';
import BeneficiarioGrid from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioGrid.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PrintBeneficiarioModal from '@/Components/Organisms/AjudaHumanitaria/Print/PrintBeneficiarioModal.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import { useMobile } from '@/composables/useMobile';

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
    default: true,
  },
  canDelete: {
    type: Boolean,
    default: true,
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

const viewMode = ref('grid');
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
// Modal de Exportação CSV
// =========================
const showExportModal = ref(false);

function handleExportCsv(params) {
  // TODO: Implementar lógica de exportação real
  console.log('Exportar:', params); 
  showExportModal.value = false;
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
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .beneficiarios-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .beneficiarios-container {
    padding: 2rem 2.5rem;
  }
}
</style>
