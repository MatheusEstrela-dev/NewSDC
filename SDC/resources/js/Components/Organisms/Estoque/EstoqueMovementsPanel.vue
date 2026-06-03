<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Movimentacoes recentes</h3>
      <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Entradas, saidas, ajustes e montagem</p>
    </div>

    <div class="divide-y divide-slate-200 dark:divide-slate-800">
      <article v-for="movimentacao in movimentacoes" :key="movimentacao.id" class="p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700 dark:border-blue-500/25 dark:bg-blue-500/15 dark:text-blue-300">
                {{ movimentacao.tipo }}
              </span>
              <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(movimentacao.data) }}</span>
            </div>
            <h4 class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ movimentacao.produto }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ movimentacao.motivo }} / {{ movimentacao.referencia }}
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ movimentacao.quantidade }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ movimentacao.status }}</div>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
defineProps({
  movimentacoes: {
    type: Array,
    default: () => [],
  },
});

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('pt-BR');
}
</script>
