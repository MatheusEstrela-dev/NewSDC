<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import TreinamentoFiltersSection from '@/Components/Organisms/Treinamento/TreinamentoFiltersSection.vue';
import TreinamentoGrid from '@/Components/Organisms/Treinamento/TreinamentoGrid.vue';
import TreinamentoStatsCards from '@/Components/Organisms/Treinamento/TreinamentoStatsCards.vue';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';

// Formatador de data seguro (evita Invalid Date em strings já formatadas dd/mm/yyyy)
const formatDate = (dateValue) => {
  if (!dateValue) return '—';
  const str = String(dateValue).trim();
  // Se a string já tiver barras, assume-se que está formatada ou parcialmente preenchida
  if (str.includes('/')) return str;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return str;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

// Detecção mobile
const { isMobile } = useMobile();

const props = defineProps({
  treinamentos: {
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

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter', 'filter-change', 'filter-reset']);

const viewMode = ref('table');
const localFilters = ref({ ...props.filters });

// Card de estatistica como filtro rapido: recebe o status ('' = Total, limpa o status)
// e preserva os demais filtros ativos (search, tipo).
const handleStatFilter = (status) => {
  const merged = { ...localFilters.value, status: status || undefined };
  localFilters.value = merged;
  emit('filter', merged);
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
import { useExport } from '@/Composables/useExport';

const {
  showExportModal,
  handleExport: triggerExport
} = useExport('treinamentos.export');

function handleExportCsv(params) {
  triggerExport(params, localFilters.value);
}
</script>

<template>
  <div class="treinamentos-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Treinamentos"
      description="Gestão de treinamentos e cursos"
      :icon="BookOpenIcon"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Oculto em mobile -->
          <ViewModeToggle v-model="viewMode" />

          <!-- Botão Exportar -->
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="showExportModal = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <!-- Botao Criar - Responsivo -->
          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="emit('create')"
          >
            <span class="hidden sm:inline">Novo Treinamento</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Modal de Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Treinamentos"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Statistics Cards -->
    <TreinamentoStatsCards :statistics="statistics" class="mb-6" @filter="handleStatFilter" />

    <!-- Filters -->
    <TreinamentoFiltersSection
      :filters="localFilters"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />

    <!-- Mobile: Sempre Grade | Desktop: Grade ou Tabela -->
    <TreinamentoGrid
      v-if="viewMode === 'grid' || isMobile"
      :treinamentos="treinamentos"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="emit('view', $event)"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <div v-else-if="viewMode === 'table' && !isMobile" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Título</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Tipo</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Instrutor</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Período</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="treinamento in treinamentos" :key="treinamento.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900 dark:text-white">{{ treinamento.titulo }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ treinamento.descricao }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                {{ treinamento.tipo }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                treinamento.status === 'CONCLUIDO' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                treinamento.status === 'EM_ANDAMENTO' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300'
              ]">
                {{ treinamento.status?.replace('_', ' ') || 'Planejado' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ treinamento.instrutor || '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              {{ formatDate(treinamento.data_inicio) }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end">
                <ActionButton
                  module="treinamento"
                  resource="cursos"
                  size="sm"
                  :actions="[
                    { action: 'view',   handler: () => emit('view', treinamento.id) },
                    { action: 'edit',   handler: () => emit('edit', treinamento.id),   allowed: canEdit },
                    { action: 'delete', handler: () => emit('delete', treinamento.id), allowed: canDelete },
                  ]"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!treinamentos || treinamentos.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Nenhum treinamento encontrado
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

<style scoped>
.treinamentos-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
