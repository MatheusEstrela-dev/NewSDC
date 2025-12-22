<template>
  <span :class="pillClasses">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  situacao: {
    type: String,
    default: '',
  },
});

const config = {
  aguardando_analise: { label: 'Aguardando Análise', classes: 'bg-amber-500/20 text-amber-300 border border-amber-500/20' },
  em_edicao: { label: 'Em edição', classes: 'bg-red-500/20 text-red-300 border border-red-500/20' },
  finalizado: { label: 'Finalizado', classes: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/20' },
};

const label = computed(() => config[props.situacao]?.label || '—');
const pillClasses = computed(() => {
  return [
    'px-3 py-1 rounded-full text-xs font-semibold',
    config[props.situacao]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20',
  ].join(' ');
});
</script>


