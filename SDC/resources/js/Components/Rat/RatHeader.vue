<template>
  <div class="flex flex-col gap-3 mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-slate-200 dark:border-slate-700/50">
    <!-- Row 1: Icon + Title + Status + BOS + Timestamp -->
    <div class="flex items-start sm:items-center gap-3">
      <!-- Icon -->
      <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>

      <!-- Title + Status + BOS -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            {{ pageTitle }}
          </h1>
          <span
            v-if="isCreate || rat.status"
            :class="['px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-md sm:rounded-lg text-xs font-semibold border whitespace-nowrap', isCreate ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30' : getStatusClass(rat.status)]"
          >
            {{ isCreate ? 'Criando' : getStatusLabel(rat.status) }}
          </span>
        </div>

        <!-- Número do BOS - destaque principal, visível em todos os tamanhos -->
        <div v-if="rat.numero_bos || rat.protocolo" class="mt-2 flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Número do BOS</span>
          <span class="text-base sm:text-xl font-black font-mono text-blue-700 dark:text-blue-300 tracking-widest bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-700/50">
            {{ rat.numero_bos || rat.protocolo }}
          </span>
        </div>

        <!-- RATs relacionados (número do BOS, não UUID) -->
        <div v-if="rat.ocorrencia_origem || rat.ocorrencias_filhas?.length" class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 items-center">
          <div v-if="rat.ocorrencia_origem" class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            <span>Origem:</span>
            <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ rat.ocorrencia_origem.numero_bos }}</span>
          </div>
          <div v-if="rat.ocorrencias_filhas?.length" class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            <span>Relacionados:</span>
            <span v-for="(f, i) in rat.ocorrencias_filhas" :key="f.id" class="font-mono font-semibold text-slate-700 dark:text-slate-300">
              {{ f.numero_bos }}<span v-if="i < rat.ocorrencias_filhas.length - 1">,&nbsp;</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Última Atualização - visível apenas em sm+ -->
      <div class="hidden sm:flex items-center gap-2 text-sm bg-slate-100 dark:bg-slate-800/50 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700/50 flex-shrink-0">
        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-slate-600 dark:text-slate-300 font-medium">{{ formattedLastUpdate }}</span>
      </div>
    </div>

    <!-- Row 2: Timestamp em mobile -->
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
  if (!props.rat?.id || props.isCreate) return 'Criar Boletim de Ocorrência';
  if (props.viewOnly) return 'Visualizar Boletim de Ocorrência';
  return 'Editar Boletim de Ocorrência';
});

const formattedLastUpdate = computed(() => {
  return formatDateTime(props.lastUpdate) || formatDateTime(new Date());
});

function getStatusClass(status) {
  const classes = {
    em_andamento: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30',
    finalizado:   'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
    arquivado:    'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30',
  };
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30';
}

function getStatusLabel(status) {
  const labels = {
    em_andamento: 'Em Andamento',
    finalizado:   'Finalizado',
    arquivado:    'Arquivado',
  };
  return labels[status] || status;
}
</script>
