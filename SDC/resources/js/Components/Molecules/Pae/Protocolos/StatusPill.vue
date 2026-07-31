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
  novo: {
    label: 'Novo',
    classes: 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/20',
  },
  entrada_processo: {
    label: 'Entrada do Processo',
    classes: 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-500/20',
  },
  criacao_sdc: {
    label: 'Criação no SDC',
    classes: 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-500/20',
  },
  gerenciamento: {
    label: 'Gerenciamento',
    classes: 'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-200 border border-blue-300 dark:border-blue-400/20',
  },
  notificacao: {
    label: 'Notificação',
    classes: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/20',
  },
  analise: {
    label: 'Análise',
    classes: 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-500/20',
  },
  aprovado: {
    label: 'Aprovado',
    classes: 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-500/20',
  },
  reprovado: {
    label: 'Reprovado',
    classes: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-500/20',
  },
  ccpae: {
    label: 'CCPAE',
    classes: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/20',
  },
  ativo_3_anos: {
    label: 'Ativo (3 anos)',
    classes: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-400/20',
  },
  suspenso: {
    label: 'Suspenso',
    classes: 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-500/20',
  },
  revogado: {
    label: 'Revogado',
    classes: 'bg-red-100 dark:bg-red-700/20 text-red-400 border border-red-300 dark:border-red-700/20',
  },
  esperar_tratativa: {
    label: 'Aguardando Tratativa',
    classes: 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300 border border-orange-300 dark:border-orange-500/20',
  },
  dilacao: {
    label: 'Dilatação',
    classes: 'bg-orange-100 dark:bg-orange-500/15 text-orange-700 dark:text-orange-200 border border-orange-300 dark:border-orange-400/20',
  },
  aguardando_analise: {
    label: 'Aguardando Análise',
    classes: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/20',
  },
  em_edicao: {
    label: 'Em edição',
    classes: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-500/20',
  },
  finalizado: {
    label: 'Finalizado',
    classes: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/20',
  },
};

const label = computed(() => config[props.situacao]?.label || props.situacao || '—');
const pillClasses = computed(() => {
  return [
    'px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap',
    config[props.situacao]?.classes || 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/20',
  ].join(' ');
});
</script>
