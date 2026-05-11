<template>
  <span :class="badgeClasses">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    default: 'disponivel',
  },
});

const statusMap = {
  disponivel: {
    label: 'Disponivel',
    classes: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/25',
  },
  emprestado: {
    label: 'Emprestado',
    classes: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/25',
  },
  manutencao: {
    label: 'Manutencao',
    classes: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/25',
  },
  baixado: {
    label: 'Baixado',
    classes: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600/40',
  },
};

const meta = computed(() => statusMap[props.status] ?? statusMap.disponivel);
const label = computed(() => meta.value.label);
const badgeClasses = computed(() => [
  'inline-flex items-center rounded-md border px-2 py-1 text-xs font-semibold whitespace-nowrap',
  meta.value.classes,
]);
</script>
