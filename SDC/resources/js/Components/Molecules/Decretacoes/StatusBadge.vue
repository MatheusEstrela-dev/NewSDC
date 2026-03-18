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
    classes: 'bg-sky-500/20 text-sky-300 border border-sky-500/30'
  },
  'Aguardando Análise do Estado': {
    label: 'Aguardando Analise',
    classes: 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
  },
  'Em análise pelo Estado': {
    label: 'Em Analise',
    classes: 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30'
  },
  'Aguardando ajustes do município': {
    label: 'Aguardando Ajustes',
    classes: 'bg-orange-500/20 text-orange-300 border border-orange-500/30'
  },
  'Reconhecido pelo Estado / Aguardando análise da União': {
    label: 'Rec. Estado / Aguard. Uniao',
    classes: 'bg-lime-500/20 text-lime-300 border border-lime-500/30'
  },
  'Reconhecido pelo Estado e pela União': {
    label: 'Reconhecido (Completo)',
    classes: 'bg-emerald-500/25 text-emerald-300 border border-emerald-500/40'
  },
  'Reconhecido somente pelo Estado': {
    label: 'Reconhecido (Estado)',
    classes: 'bg-green-500/20 text-green-300 border border-green-500/30'
  },
  'Reconhecido somente pela União': {
    label: 'Reconhecido (Uniao)',
    classes: 'bg-teal-500/20 text-teal-300 border border-teal-500/30'
  },
  'Não reconhecido pelo Estado': {
    label: 'Nao Reconhecido (Estado)',
    classes: 'bg-red-500/20 text-red-300 border border-red-500/30'
  },
  'Não reconhecido pela União': {
    label: 'Nao Reconhecido (Uniao)',
    classes: 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
  },
  'Não reconhecido pelo Estado e União': {
    label: 'Nao Reconhecido',
    classes: 'bg-red-600/25 text-red-300 border border-red-500/40'
  },
  'Pendente': {
    label: 'Pendente',
    classes: 'bg-violet-500/20 text-violet-300 border border-violet-500/30'
  },
  'Em andamento': {
    label: 'Em Andamento',
    classes: 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
  },
  'Concluido': {
    label: 'Concluido',
    classes: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
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
