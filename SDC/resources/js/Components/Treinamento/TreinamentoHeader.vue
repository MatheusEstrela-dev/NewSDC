<template>
  <div class="flex flex-col gap-3 mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-slate-200 dark:border-slate-700/50">
    <!-- Row 1: Icon + Title + Status -->
    <div class="flex items-start sm:items-center gap-3">
      <div class="w-12 h-12 sm:w-14 sm:h-14 flex items-center justify-center shrink-0">
        <img :src="moduleIcon('treinamento')" alt="" class="h-full w-full object-contain" />
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            {{ treinamento.titulo }}
          </h1>
          <span
            v-if="treinamento.status_label"
            :class="['px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-md sm:rounded-lg text-xs font-semibold border whitespace-nowrap', getStatusClass(treinamento.status)]"
          >
            {{ treinamento.status_label }}
          </span>
        </div>

        <div class="mt-2 flex items-center gap-2 flex-wrap">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">{{ treinamento.tipo_label }}</span>
          <span class="text-slate-300 dark:text-slate-600">·</span>
          <span class="text-sm text-slate-600 dark:text-slate-300">{{ treinamento.categoria_label }}</span>
          <span class="text-slate-300 dark:text-slate-600">·</span>
          <span class="text-sm text-slate-600 dark:text-slate-300">{{ treinamento.carga_horaria }}h</span>
        </div>
      </div>

      <!-- Vagas - visivel apenas em sm+ -->
      <div class="hidden sm:flex items-center gap-2 text-sm bg-slate-100 dark:bg-slate-800/50 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700/50 flex-shrink-0">
        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span class="text-slate-600 dark:text-slate-300 font-medium">{{ vagasLabel }}</span>
      </div>
    </div>

    <!-- Row 2: Vagas em mobile -->
    <div class="flex sm:hidden items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      <span>{{ vagasLabel }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  treinamento: {
    type: Object,
    required: true,
  },
});

const vagasLabel = computed(() => {
  if (!props.treinamento.numero_vagas) return 'Vagas ilimitadas';
  return `${props.treinamento.vagas_disponiveis} de ${props.treinamento.numero_vagas} vagas`;
});

// Espelha as cores do Badge (Atoms/Badge) - se as cores de status mudarem la,
// precisam mudar aqui tambem para o show e a listagem nao divergirem.
function getStatusClass(status) {
  const classes = {
    RASCUNHO: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30',
    PUBLICADO: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30',
    EM_ANDAMENTO: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
    CONCLUIDO: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
    CANCELADO: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/30',
  };
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30';
}
</script>
