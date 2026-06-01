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

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
      <div
        v-for="i in 4"
        :key="i"
        class="bg-white dark:bg-slate-900/60 rounded-lg border border-slate-200 dark:border-slate-700/30 p-6 animate-pulse"
      >
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/4 mb-4"></div>
        <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
      </div>
    </div>

    <ListEmptyState
      v-if="!loading && beneficiarios.length === 0"
      title="Nenhum beneficiário encontrado"
      :helper="emptyMessage || 'Comece criando um novo beneficiário para gerenciar a ajuda humanitária.'"
    />
  </div>
</template>

<script setup>
import BeneficiarioCard from '@/Components/Molecules/AjudaHumanitaria/BeneficiarioCard.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

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
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: '',
  },
});

defineEmits(['view', 'print', 'edit', 'delete']);
</script>
