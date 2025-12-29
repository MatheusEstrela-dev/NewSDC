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
        <div class="flex items-center gap-3">
          <!-- Toggle Grade/Tabela -->
          <div class="flex items-center gap-1 bg-white dark:bg-slate-800/50 rounded-lg p-1 border border-slate-300 dark:border-slate-700/50">
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
          <!-- Botão Criar -->
          <Button
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="$emit('create')"
          >
            Novo Beneficiário
          </Button>
        </div>
      </template>
    </PageHeader>

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

    <!-- Grid de Beneficiários -->
    <BeneficiarioGrid
      v-if="viewMode === 'grid'"
      :beneficiarios="beneficiarios"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @edit="(id) => $emit('edit', id)"
      @delete="(id) => $emit('delete', id)"
    />

    <!-- Table de Beneficiários -->
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
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
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.municipio || '—' }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <button @click="$emit('view', beneficiario.id)" class="p-1.5 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200" title="Visualizar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button v-if="canEdit" @click="$emit('edit', beneficiario.id)" class="p-1.5 rounded-lg text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-all duration-200" title="Editar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button v-if="canDelete" @click="$emit('delete', beneficiario.id)" class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200" title="Excluir">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
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
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import BeneficiarioStatsCards from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioStatsCards.vue';
import BeneficiarioFiltersSection from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioFiltersSection.vue';
import BeneficiarioGrid from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioGrid.vue';

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

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter', 'filter-change', 'filter-reset']);

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
