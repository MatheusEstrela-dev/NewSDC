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
    required: true,
  },
});

const statusConfig = {
  'Registro': {
    label: 'Registro',
    classes: 'bg-blue-500/20 text-blue-300 border border-blue-500/20'
  },
  'Aguardando Análise do Estado': {
    label: 'Aguardando Análise',
    classes: 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/20'
  },
  'Em análise pelo Estado': {
    label: 'Em Análise',
    classes: 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/20'
  },
  'Aguardando ajustes do município': {
    label: 'Aguardando Ajustes',
    classes: 'bg-orange-500/20 text-orange-300 border border-orange-500/20'
  },
  'Reconhecido pelo Estado / Aguardando análise da União': {
    label: 'Reconhecido (Estado)',
    classes: 'bg-green-500/20 text-green-300 border border-green-500/20'
  },
  'Reconhecido pelo Estado e pela União': {
    label: 'Reconhecido (Completo)',
    classes: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/20'
  },
  'Reconhecido somente pelo Estado': {
    label: 'Reconhecido (Estado)',
    classes: 'bg-green-500/20 text-green-300 border border-green-500/20'
  },
  'Reconhecido somente pela União': {
    label: 'Reconhecido (União)',
    classes: 'bg-green-500/20 text-green-300 border border-green-500/20'
  },
  'Não reconhecido pelo Estado': {
    label: 'Não Reconhecido',
    classes: 'bg-red-500/20 text-red-300 border border-red-500/20'
  },
  'Não reconhecido pela União': {
    label: 'Não Reconhecido',
    classes: 'bg-red-500/20 text-red-300 border border-red-500/20'
  },
  'Não reconhecido pelo Estado e União': {
    label: 'Não Reconhecido',
    classes: 'bg-red-500/20 text-red-300 border border-red-500/20'
  },
};

const label = computed(() => statusConfig[props.status]?.label || props.status);
const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    statusConfig[props.status]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20',
  ].join(' ');
});
</script>
