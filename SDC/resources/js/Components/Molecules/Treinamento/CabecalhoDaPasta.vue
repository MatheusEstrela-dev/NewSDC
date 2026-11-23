<script setup>
import { ChevronLeftIcon } from '@heroicons/vue/24/outline';

/*
 * Cabecalho de navegacao de dentro de uma pasta de categoria: breadcrumb
 * "Todas as categorias / <categoria>", contagem e descricao. `voltar` leva
 * de volta as pastas (a pagina navega para a raiz, sem ?categoria=).
 */
defineProps({
  categoria: { type: Object, required: true },
});

defineEmits(['voltar']);
</script>

<template>
  <header class="mb-5 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/60 dark:bg-slate-800/60">
    <nav aria-label="Navegação das categorias" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
      <button
        type="button"
        class="inline-flex min-h-[44px] items-center gap-1 font-medium text-blue-600 hover:underline focus:outline-none focus-visible:underline dark:text-blue-400 sm:min-h-0"
        @click="$emit('voltar')"
      >
        <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
        Todas as categorias
      </button>
      <span class="text-slate-400" aria-hidden="true">/</span>
      <span class="font-semibold text-slate-800 dark:text-slate-100" aria-current="page">{{ categoria.rotulo }}</span>
      <span class="ml-auto rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300">
        {{ categoria.total_rotulo }}
      </span>
    </nav>
    <p v-if="categoria.descricao" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ categoria.descricao }}</p>
  </header>
</template>
