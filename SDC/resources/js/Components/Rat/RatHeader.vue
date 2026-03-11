<template>
  <div class="flex flex-col gap-3 mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-slate-200 dark:border-slate-700/50">
    <!-- Row 1: Icon + Title + Status + Timestamp -->
    <div class="flex items-center gap-3">
      <!-- Icon - menor em mobile -->
      <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>

      <!-- Title + Status inline -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            {{ pageTitle }}
          </h1>
          <span v-if="rat.status" :class="['px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-md sm:rounded-lg text-xs font-semibold border whitespace-nowrap', getStatusClass(rat.status)]">
            {{ getStatusLabel(rat.status) }}
          </span>
        </div>
        <p v-if="rat.protocolo" class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5 font-mono truncate">
          {{ rat.protocolo }}
        </p>
      </div>

      <!-- Última Atualização - visível apenas em sm+ -->
      <div class="hidden sm:flex items-center gap-2 text-sm bg-slate-100 dark:bg-slate-800/50 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700/50 flex-shrink-0">
        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-slate-600 dark:text-slate-300 font-medium">{{ formattedLastUpdate }}</span>
      </div>
    </div>

    <!-- Row 2: Timestamp em mobile (visivel apenas em < sm) -->
    <div class="flex sm:hidden items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>Atualizado: {{ formattedLastUpdate }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDateTime } from '../../utils/dateFormatter';

const props = defineProps({
  rat: {
    type: Object,
    required: true,
  },
  lastUpdate: {
    type: String,
    default: null,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  isCreate: {
    type: Boolean,
    default: false,
  },
});

const pageTitle = computed(() => {
  if (props.isCreate) return 'Novo RAT';
  if (props.viewOnly) return 'Visualizar RAT';
  return 'Editar RAT';
});

const formattedLastUpdate = computed(() => {
  return props.lastUpdate || formatDateTime(new Date());
});

function getStatusClass(status) {
  const classes = {
    rascunho: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
    finalizado: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
    cancelado: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/30',
  };
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30';
}

function getStatusLabel(status) {
  const labels = {
    rascunho: 'Rascunho',
    finalizado: 'Finalizado',
    cancelado: 'Cancelado',
  };
  return labels[status] || status;
}
</script>
