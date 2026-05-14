<template>
  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', cor]">
    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="ponto"></span>
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  estado: { type: String, required: true }, // 'rascunho' | 'ativo' | 'encerrado'
});

const config = {
  rascunho:  { cor: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',    ponto: 'bg-amber-500',   label: 'Rascunho' },
  ativo:     { cor: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300', ponto: 'bg-emerald-500', label: 'Ativo' },
  encerrado: { cor: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',        ponto: 'bg-slate-500',   label: 'Encerrado' },
};

const cor   = computed(() => config[props.estado]?.cor   ?? config.rascunho.cor);
const ponto = computed(() => config[props.estado]?.ponto ?? config.rascunho.ponto);
const label = computed(() => config[props.estado]?.label ?? props.estado);
</script>
