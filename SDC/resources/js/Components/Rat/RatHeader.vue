<template>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4 pb-4 border-b border-slate-700/50">
    <!-- Left: Title and Status -->
    <div class="flex items-center gap-4">
      <!-- Icon -->
      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      
      <!-- Title -->
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
          Novo RAT
          <span v-if="rat.status" :class="['px-2.5 py-1 rounded-lg text-xs font-semibold border', getStatusClass(rat.status)]">
            {{ getStatusLabel(rat.status) }}
          </span>
        </h1>
        <p v-if="rat.protocolo" class="text-sm text-slate-400 mt-0.5 font-mono">
          Protocolo: {{ rat.protocolo }}
        </p>
      </div>
    </div>

    <!-- Right: Last Update -->
    <div class="flex items-center gap-2 text-sm bg-slate-800/50 px-4 py-2 rounded-lg border border-slate-700/50">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="text-slate-500">Atualização:</span>
      <span class="text-slate-300 font-medium">{{ formattedLastUpdate }}</span>
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
});

const formattedLastUpdate = computed(() => {
  return props.lastUpdate || formatDateTime(new Date());
});

function getStatusClass(status) {
  const classes = {
    rascunho: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    finalizado: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    cancelado: 'bg-red-500/10 text-red-400 border-red-500/30',
  };
  return classes[status] || 'bg-slate-500/10 text-slate-400 border-slate-500/30';
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

