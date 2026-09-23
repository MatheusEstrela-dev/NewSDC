<template>
  <span :class="['px-2 py-0.5 rounded-md text-xs font-medium inline-flex items-center', badgeClass]">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  prioridade: { type: [String, Number], required: true },
  label: { type: String, default: '' },
});

const badgeClass = computed(() => {
  // Converte string para numero para garantir match com enum Prioridade::cases
  const prioridadeVal = Number(props.prioridade);
  const classes = {
    1: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', // Crítica
    2: 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300', // Alta
    3: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-300', // Média
    4: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300', // Baixa
    5: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300', // Planejada
  };
  return classes[prioridadeVal] || classes[3];
});
</script>
