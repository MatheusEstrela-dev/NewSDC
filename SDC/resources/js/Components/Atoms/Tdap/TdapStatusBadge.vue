<template>
  <span :class="badgeClasses">
    {{ active ? 'Ativo' : 'Inativo' }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
});

const badgeClasses = computed(() => {
  const base = 'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold';

  if (props.active) {
    return `${base} border border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300`;
  }

  return `${base} border border-slate-500/25 bg-slate-500/10 text-slate-600 dark:text-slate-300`;
});
</script>
