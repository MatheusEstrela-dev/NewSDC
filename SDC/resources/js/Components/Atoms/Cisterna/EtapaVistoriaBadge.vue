<template>
  <span :class="classes" :title="titulo">{{ sigla }}</span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Etapa da fiscalizacao, em sigla curta, para caber na coluna da listagem sem
 * quebrar a linha.
 *
 * A prop `concluida` controla o preenchimento: etapa pendente aparece so com
 * contorno. E o que substitui os tres `whereHas` que o legado fazia por linha
 * para saber se cada etapa estava validada.
 */
const props = defineProps({
  etapa: { type: String, required: true },
  concluida: { type: Boolean, default: false },
});

const SIGLAS = {
  fornecedor: 'F',
  compdec: 'C',
  cedec: 'CD',
};

const TITULOS = {
  fornecedor: 'Fornecedor',
  compdec: 'COMPDEC',
  cedec: 'CEDEC',
};

const BASE = 'inline-flex h-5 min-w-5 items-center justify-center rounded px-1 text-[10px] font-bold ring-1 ring-inset';

const PREENCHIDA = 'bg-emerald-600 text-white ring-emerald-700 dark:bg-emerald-500 dark:ring-emerald-400';
const PENDENTE = 'bg-transparent text-slate-400 ring-slate-300 dark:text-slate-500 dark:ring-slate-600';

const sigla = computed(() => SIGLAS[props.etapa] ?? '?');

const titulo = computed(
  () => `${TITULOS[props.etapa] ?? props.etapa}: ${props.concluida ? 'concluida' : 'pendente'}`,
);

const classes = computed(() => `${BASE} ${props.concluida ? PREENCHIDA : PENDENTE}`);
</script>
