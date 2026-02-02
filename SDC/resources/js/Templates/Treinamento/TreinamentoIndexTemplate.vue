<script setup>
import { ref } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import TreinamentoStatsCards from '@/Components/Organisms/Treinamento/TreinamentoStatsCards.vue';
import TreinamentoFiltersSection from '@/Components/Organisms/Treinamento/TreinamentoFiltersSection.vue';
import TreinamentoGrid from '@/Components/Organisms/Treinamento/TreinamentoGrid.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useMobile } from '@/composables/useMobile';

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
  canManage: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter', 'filter-change', 'filter-reset']);

const viewMode = ref('grid');
const localFilters = ref({ ...props.filters });

const handleStatFilter = (statId) => {
  // Mapear ID do stat para filtro de status
  const statusMap = {
    total: null,
    planejados: 'PLANEJADO',
    em_andamento: 'EM_ANDAMENTO',
    concluidos: 'CONCLUIDO',
  };

  emit('filter', { status: statusMap[statId] });
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
            v-if="canManage"
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
      :can-edit="canManage"
      :can-delete="canManage"
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
              {{ treinamento.data_inicio ? new Date(treinamento.data_inicio).toLocaleDateString('pt-BR') : '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <button @click="emit('view', treinamento.id)" class="p-1.5 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200" title="Visualizar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button v-if="canManage" @click="emit('edit', treinamento.id)" class="p-1.5 rounded-lg text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-all duration-200" title="Editar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
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
    <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-between items-center">
      <Text size="sm" color="muted">
        Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
      </Text>

      <div class="flex gap-2">
        <button
          :disabled="pagination.current_page === 1"
          class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>
        <div class="flex items-center px-4">
          <Text size="sm">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </Text>
        </div>
        <button
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Próxima
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.treinamentos-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  /* Padding removed for global alignment */
}
</style>
