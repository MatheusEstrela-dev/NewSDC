<template>
  <div>
    <!-- Grid de Cards -->
    <div v-if="!loading && beneficiarios.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
      <BeneficiarioCard
        v-for="beneficiario in beneficiarios"
        :key="beneficiario.id"
        :beneficiario="beneficiario"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @view="$emit('view', beneficiario.id)"
        @print="$emit('print', beneficiario.id)"
        @edit="$emit('edit', beneficiario.id)"
        @delete="$emit('delete', beneficiario.id)"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
      <div
        v-for="i in 4"
        :key="i"
        class="bg-slate-900/60 dark:bg-slate-900/60 bg-white rounded-lg border border-slate-700/30 dark:border-slate-700/30 border-slate-200 p-6 animate-pulse"
      >
        <div class="h-4 bg-slate-700 dark:bg-slate-700 bg-slate-200 rounded w-1/4 mb-4"></div>
        <div class="h-6 bg-slate-700 dark:bg-slate-700 bg-slate-200 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-slate-700 dark:bg-slate-700 bg-slate-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && beneficiarios.length === 0" class="text-center py-12 md:py-16">
      <svg class="mx-auto h-12 w-12 md:h-16 md:w-16 text-slate-400 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <h3 class="text-base md:text-lg font-medium text-slate-300 dark:text-slate-300 text-slate-900 mb-1">
        Nenhum beneficiário encontrado
      </h3>
      <p class="text-sm text-slate-400 dark:text-slate-500 text-slate-600">
        {{ emptyMessage || 'Comece criando um novo beneficiário para gerenciar a ajuda humanitária.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import BeneficiarioCard from '@/Components/Molecules/AjudaHumanitaria/BeneficiarioCard.vue';

defineProps({
  beneficiarios: {
    type: Array,
    default: () => [],
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
  emptyMessage: {
    type: String,
    default: '',
  },
});

defineEmits(['view', 'print', 'edit', 'delete']);
</script>
