<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', cor]">
    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="ponto"></span>
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  estado: { type: String, required: true },
});

const config = {
  rascunho:           { cor: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',       ponto: 'bg-slate-500',   label: 'Rascunho' },
  em_habilitacao:     { cor: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',            ponto: 'bg-sky-500',     label: 'Em habilitação' },
  decreto_pendente:   { cor: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',        ponto: 'bg-rose-500',    label: 'Aguardando decreto' },
  em_licitacao:       { cor: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',    ponto: 'bg-amber-500',   label: 'Em licitação' },
  em_execucao:        { cor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',        ponto: 'bg-blue-500',    label: 'Em execução' },
  liquidacao_pendente:{ cor: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',ponto: 'bg-purple-500',  label: 'Aguardando liquidação' },
  pago:               { cor: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300', ponto: 'bg-emerald-500', label: 'Pago' },
  encerrado:          { cor: 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400',       ponto: 'bg-slate-400',   label: 'Encerrado' },
};

const cor   = computed(() => config[props.estado]?.cor   ?? config.rascunho.cor);
const ponto = computed(() => config[props.estado]?.ponto ?? config.rascunho.ponto);
const label = computed(() => config[props.estado]?.label ?? props.estado);
</script>
