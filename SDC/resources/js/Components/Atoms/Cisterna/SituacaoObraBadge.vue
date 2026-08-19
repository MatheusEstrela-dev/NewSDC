<template>
  <span :class="classes">{{ rotulo }}</span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Andamento da obra. Eixo ORTOGONAL a situacao da analise: um cadastro
 * aprovado pode estar em processamento, e um em edicao pode ter obra
 * instalada. No legado os dois eixos eram confundidos numa coluna so.
 *
 * Cores no front, nao em metodo do enum PHP: o Tailwind nao escaneia
 * `app/**\/*.php`.
 */
const props = defineProps({
  valor: { type: String, required: true },
  rotulo: { type: String, default: '' },
});

const CORES = {
  processamento: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30',
  envio_instalacao: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-400/30',
  instalado: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30',
};

const BASE = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap';

const classes = computed(() => `${BASE} ${CORES[props.valor] ?? CORES.processamento}`);
</script>
