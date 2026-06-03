<template>
  <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="mb-3 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Alertas operacionais</h3>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Validade, minimo e bloqueios para conferencia</p>
      </div>
      <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" />
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
      <div
        v-for="alerta in alertas"
        :key="alerta.id"
        class="rounded-lg border px-3 py-3"
        :class="alertClasses(alerta.nivel)"
      >
        <div class="text-xs font-semibold uppercase tracking-wide">{{ alerta.tipo }}</div>
        <p class="mt-1 text-sm font-medium">{{ alerta.titulo }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
  alertas: {
    type: Array,
    default: () => [],
  },
});

function alertClasses(nivel) {
  if (nivel === 'danger') {
    return 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/25 dark:bg-red-500/15 dark:text-red-300';
  }

  return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/15 dark:text-amber-300';
}
</script>
