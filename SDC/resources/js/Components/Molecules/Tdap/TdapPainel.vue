<template>
  <section class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/40 dark:bg-slate-900/40">
    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/40">
      <div class="flex min-w-0 items-center gap-3">
        <span
          v-if="icone"
          class="shrink-0 rounded-lg bg-blue-50 p-2 text-blue-600 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/25"
        >
          <component :is="icone" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <p v-if="rotulo" class="mb-1 text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
            {{ rotulo }}
          </p>
          <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ titulo }}</h3>
          <p v-if="subtitulo" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ subtitulo }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <slot name="acoes" />
        <Link
          v-if="linkHref"
          :href="linkHref"
          class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
        >
          {{ linkRotulo }}
          <ChevronRightIcon />
        </Link>
      </div>
    </header>

    <div class="flex-1">
      <slot />
    </div>

    <footer v-if="$slots.rodape" class="border-t border-slate-200 px-5 py-4 dark:border-slate-700/40">
      <slot name="rodape" />
    </footer>
  </section>
</template>

<script setup>
/**
 * Moldura dos blocos do dashboard do TDAP: cabecalho (rotulo de secao, titulo,
 * subtitulo, icone opcional e link "ver mais"), corpo livre e rodape opcional.
 * O corpo nao tem padding proprio -- grafico e lista com divisorias de ponta a
 * ponta pedem respiros diferentes.
 */
import { Link } from '@inertiajs/vue3';
import ChevronRightIcon from '@/Components/Icons/ChevronRightIcon.vue';

defineProps({
  titulo: { type: String, required: true },
  rotulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' },
  icone: { type: [Object, Function], default: null },
  linkHref: { type: String, default: '' },
  linkRotulo: { type: String, default: 'Ver todos' },
});
</script>
