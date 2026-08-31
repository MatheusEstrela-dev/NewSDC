<template>
  <span :class="classes">{{ rotulo }}</span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Situacao da analise do cadastro. Atomo burro: recebe valor e rotulo prontos,
 * nao conhece o dominio nem faz consulta.
 *
 * As classes de cor moram AQUI e nao num metodo do enum em PHP: o Tailwind nao
 * escaneia `app/**\/*.php`, entao classe que so existe em string do backend nao
 * entra no CSS gerado.
 */
const props = defineProps({
  valor: { type: String, required: true },
  rotulo: { type: String, default: '' },
});

const CORES = {
  em_edicao: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/30',
  aprovado: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30',
  reprovado: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/30',
  ressalva: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-400/30',
  // Desconsiderado e duplicado saem da lista ativa: cinza nas duas, porque a
  // distincao entre elas nao muda o que o usuario faz.
  desconsiderado: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30',
  duplicado: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30',
};

const BASE = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap';

const classes = computed(() => `${BASE} ${CORES[props.valor] ?? CORES.em_edicao}`);
</script>
