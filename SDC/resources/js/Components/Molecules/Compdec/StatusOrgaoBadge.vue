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
    required: false,
    default: null,
  },
});

const statusConfig = {
  ativo: {
    label: 'Ativo',
    classes: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/30',
  },
  inativo: {
    label: 'Inativo',
    classes: 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/30',
  },
  em_implantacao: {
    label: 'Em Implantacao',
    classes: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/30',
  },
  suspenso: {
    label: 'Suspenso',
    classes: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-500/30',
  },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return statusConfig[props.status]?.label || props.status;
});

const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    props.status
      ? (statusConfig[props.status]?.classes || 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/20')
      : 'bg-slate-100 dark:bg-slate-500/20 text-slate-400 border border-slate-300 dark:border-slate-500/20',
  ].join(' ');
});
</script>
