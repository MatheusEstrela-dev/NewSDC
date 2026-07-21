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

// Cada status tem classes para light (fundo -100, texto -700/800, borda -300)
// e dark (dark:*). O light da contraste legivel; o dark preserva a composicao
// translucida original.
const statusConfig = {
  'Registro': {
    label: 'Registro',
    classes: 'bg-sky-100 text-sky-700 border border-sky-300 dark:bg-sky-500/20 dark:text-sky-300 dark:border-sky-500/30'
  },
  'TEMPORARIO': {
    label: 'TEMPORARIO',
    classes: 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30'
  },
  'Envio Direto para União': {
    label: 'Envio Direto para Uniao',
    classes: 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30'
  },
  'Aguardando Análise do Estado': {
    label: 'Aguardando Analise',
    classes: 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30'
  },
  'Em análise pelo Estado': {
    label: 'Em Analise',
    classes: 'bg-cyan-100 text-cyan-700 border border-cyan-300 dark:bg-cyan-500/20 dark:text-cyan-300 dark:border-cyan-500/30'
  },
  'Aguardando ajustes do município': {
    label: 'Aguardando Ajustes',
    classes: 'bg-orange-100 text-orange-700 border border-orange-300 dark:bg-orange-500/20 dark:text-orange-300 dark:border-orange-500/30'
  },
  'Reconhecido pelo Estado / Aguardando análise da União': {
    label: 'Rec. Estado / Aguard. Uniao',
    classes: 'bg-lime-100 text-lime-800 border border-lime-300 dark:bg-lime-500/20 dark:text-lime-300 dark:border-lime-500/30'
  },
  'Reconhecido pelo Estado e pela União': {
    label: 'Reconhecido (Completo)',
    classes: 'bg-emerald-100 text-emerald-800 border border-emerald-400 dark:bg-emerald-500/25 dark:text-emerald-300 dark:border-emerald-500/40'
  },
  'Reconhecido somente pelo Estado': {
    label: 'Reconhecido (Estado)',
    classes: 'bg-green-100 text-green-700 border border-green-300 dark:bg-green-500/20 dark:text-green-300 dark:border-green-500/30'
  },
  'Reconhecido somente pela União': {
    label: 'Reconhecido (Uniao)',
    classes: 'bg-teal-100 text-teal-700 border border-teal-300 dark:bg-teal-500/20 dark:text-teal-300 dark:border-teal-500/30'
  },
  'Não reconhecido pelo Estado': {
    label: 'Nao Reconhecido (Estado)',
    classes: 'bg-red-100 text-red-700 border border-red-300 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/30'
  },
  'Não reconhecido pela União': {
    label: 'Nao Reconhecido (Uniao)',
    classes: 'bg-rose-100 text-rose-700 border border-rose-300 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/30'
  },
  'Não reconhecido pelo Estado e União': {
    label: 'Nao Reconhecido',
    classes: 'bg-red-100 text-red-800 border border-red-400 dark:bg-red-600/25 dark:text-red-300 dark:border-red-500/40'
  },
  'Pendente': {
    label: 'Pendente',
    classes: 'bg-violet-100 text-violet-700 border border-violet-300 dark:bg-violet-500/20 dark:text-violet-300 dark:border-violet-500/30'
  },
  'Em andamento': {
    label: 'Em Andamento',
    classes: 'bg-indigo-100 text-indigo-700 border border-indigo-300 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30'
  },
  'Concluido': {
    label: 'Concluido',
    classes: 'bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30'
  },
};

const DEFAULT_CLASSES = 'bg-slate-100 text-slate-600 border border-slate-300 dark:bg-slate-500/20 dark:text-slate-400 dark:border-slate-500/20';

const label = computed(() => {
  if (!props.status) return 'N/A';
  return statusConfig[props.status]?.label || props.status;
});
const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    props.status ? (statusConfig[props.status]?.classes || DEFAULT_CLASSES) : DEFAULT_CLASSES,
  ].join(' ');
});
</script>
