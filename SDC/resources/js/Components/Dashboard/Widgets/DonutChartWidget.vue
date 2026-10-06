<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden relative group h-full flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
      <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Distribuição por Módulo</h3>
      <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Proporção de registros por área</p>
    </div>
    <div class="p-5 flex flex-1">
      <DonutDistribuicao :itens="props.moduleDistribution" />
    </div>
  </div>
</template>

<script setup>
import DonutDistribuicao from '@/Components/Molecules/Statistics/DonutDistribuicao.vue';

const props = defineProps({
  moduleDistribution: {
    type: Array,
    default: () => [],
  },
});
</script>
