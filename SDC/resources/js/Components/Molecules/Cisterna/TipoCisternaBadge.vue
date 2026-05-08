<template>
  <span :class="badgeClasses">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  tipo: { type: String, required: false, default: null },
});

const config = {
  comunitaria: { label: 'Comunitaria', classes: 'bg-violet-500/20 text-violet-300 border border-violet-500/30' },
  individual: { label: 'Individual', classes: 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' },
  escolar: { label: 'Escolar', classes: 'bg-pink-500/20 text-pink-300 border border-pink-500/30' },
};

const label = computed(() => {
  if (!props.tipo) return 'N/A';
  return config[props.tipo]?.label || props.tipo;
});

const badgeClasses = computed(() => [
  'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
  props.tipo
    ? (config[props.tipo]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20')
    : 'bg-slate-500/20 text-slate-400 border border-slate-500/20',
].join(' '));
</script>
