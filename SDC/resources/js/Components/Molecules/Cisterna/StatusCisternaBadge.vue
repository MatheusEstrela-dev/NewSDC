<template>
  <span :class="badgeClasses">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, required: false, default: null },
});

const config = {
  ativa: { label: 'Ativa', classes: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' },
  pendente: { label: 'Pendente', classes: 'bg-amber-500/20 text-amber-300 border border-amber-500/30' },
  inativa: { label: 'Inativa', classes: 'bg-slate-500/20 text-slate-300 border border-slate-500/30' },
  em_obras: { label: 'Em Obras', classes: 'bg-blue-500/20 text-blue-300 border border-blue-500/30' },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return config[props.status]?.label || props.status;
});

const badgeClasses = computed(() => [
  'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
  props.status
    ? (config[props.status]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20')
    : 'bg-slate-500/20 text-slate-400 border border-slate-500/20',
].join(' '));
</script>
