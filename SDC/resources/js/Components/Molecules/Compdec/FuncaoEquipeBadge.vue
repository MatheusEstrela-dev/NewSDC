<template>
  <span :class="badgeClasses">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  funcao: {
    type: String,
    required: false,
    default: null,
  },
});

const funcaoConfig = {
  coordenador: {
    label: 'Coordenador',
    classes: 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
  },
  agente: {
    label: 'Agente',
    classes: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
  },
  tecnico: {
    label: 'Tecnico',
    classes: 'bg-violet-500/20 text-violet-300 border border-violet-500/30',
  },
  apoio: {
    label: 'Apoio',
    classes: 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
  },
  outro: {
    label: 'Outro',
    classes: 'bg-slate-500/20 text-slate-300 border border-slate-500/30',
  },
};

const label = computed(() => {
  if (!props.funcao) return 'N/A';
  return funcaoConfig[props.funcao]?.label || props.funcao;
});

const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    props.funcao
      ? (funcaoConfig[props.funcao]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20')
      : 'bg-slate-500/20 text-slate-400 border border-slate-500/20',
  ].join(' ');
});
</script>
