<template>
  <div class="beneficiarios-container">
    <!-- Header -->
    <div class="mb-4 md:mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div class="flex-1 min-w-0">
        <Heading :level="2" color="default" class="mb-1 md:mb-2 text-xl md:text-2xl">
          Beneficiários
        </Heading>
        <Text size="sm" color="muted" class="hidden sm:block">
          Gestão de beneficiários e famílias afetadas por desastres
        </Text>
      </div>

      <!-- Botão Novo Beneficiário -->
      <button
        @click="$emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md self-start md:self-auto"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="hidden sm:inline">Novo Beneficiário</span>
        <span class="sm:hidden">Novo</span>
      </button>
    </div>

    <!-- Statistics Cards -->
    <BeneficiarioStatsCards
      :statistics="statistics"
      @filter="handleStatFilter"
      class="mb-6"
    />

    <!-- Grid de Beneficiários -->
    <BeneficiarioGrid
      :beneficiarios="beneficiarios"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @edit="(id) => $emit('edit', id)"
      @delete="(id) => $emit('delete', id)"
    />

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-6">
      <div class="flex items-center justify-between px-6 py-4 bg-slate-900/60 dark:bg-slate-900/60 bg-white rounded-lg border border-slate-700/30 dark:border-slate-700/30 border-slate-200">
        <p class="text-sm text-slate-400 dark:text-slate-400 text-slate-600">
          Mostrando {{ pagination.from || 0 }} a {{ pagination.to || 0 }} de {{ pagination.total || 0 }} resultados
        </p>
        <!-- TODO: Adicionar componente de paginação -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import BeneficiarioStatsCards from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioStatsCards.vue';
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
});

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter']);

const handleStatFilter = (filter) => {
  emit('filter', filter);
};
</script>
