<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 overflow-hidden h-full flex flex-col">
    <div class="px-4 sm:px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
      <div>
        <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">PMDA em Análise</h3>
        <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Aguardando intervenção técnica</p>
      </div>
      <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30 animate-pulse">
        {{ pmdaEmAnalise.length }} processos
      </span>
    </div>
    <div class="divide-y divide-slate-100 dark:divide-slate-800/50 flex-1 overflow-y-auto max-h-[400px]">
      <div
        v-for="item in pmdaEmAnalise"
        :key="item.id"
        class="px-4 sm:px-5 py-3 hover:bg-blue-50/50 dark:hover:bg-blue-500/5 transition-colors duration-300 group cursor-pointer"
      >
        <div class="flex items-center justify-between gap-3 mb-1">
          <span class="font-bold text-sm text-slate-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ item.protocolo }}</span>
          <span :class="statusBadgeClasses(item.statusType)" class="group-hover:scale-105 transition-transform">
            {{ item.status }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-600 dark:text-slate-400">{{ item.municipio }}</span>
          <span class="text-[11px] text-slate-500 dark:text-slate-500">{{ item.data }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const pmdaEmAnalise = ref([
  { id: 1, protocolo: 'PMDA-2024-089', municipio: 'Ouro Preto', data: 'Há 2 horas', status: 'Análise Téc.', statusType: 'warning' },
  { id: 2, protocolo: 'PMDA-2024-088', municipio: 'Mariana', data: 'Há 5 horas', status: 'Pendente Doc.', statusType: 'danger' },
  { id: 3, protocolo: 'PMDA-2024-085', municipio: 'Itabirito', data: 'Ontem', status: 'Em Revisão', statusType: 'info' },
  { id: 4, protocolo: 'PMDA-2024-082', municipio: 'Brumadinho', data: '12/03', status: 'Aprovado', statusType: 'success' },
  { id: 5, protocolo: 'PMDA-2024-080', municipio: 'Nova Lima', data: '10/03', status: 'Análise Fin.', statusType: 'primary' },
]);

function statusBadgeClasses(type) {
  const map = {
    warning: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30',
    danger: 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-500/20 dark:text-rose-400 dark:border-rose-500/30',
    info: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30',
    success: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30',
    primary: 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-500/20 dark:text-violet-400 dark:border-violet-500/30'
  };
  return `text-[10px] font-bold px-2 py-0.5 rounded-md border ${map[type] || map.info}`;
}
</script>
