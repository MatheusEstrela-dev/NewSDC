<script setup>
import { computed } from 'vue';

/* Curso em andamento de um servidor, com barra de progresso quando ha percentual. */
const props = defineProps({
  curso: { type: Object, required: true },
});

const progresso = computed(() => {
  const valor = props.curso.progresso;

  return valor === null || valor === undefined ? null : Math.min(100, Math.max(0, Number(valor)));
});
</script>

<template>
  <li class="py-3">
    <p class="break-words text-base font-semibold text-slate-900 dark:text-white">{{ curso.titulo }}<span v-if="curso.versao_rotulo"> - {{ curso.versao_rotulo }}</span></p>
    <div v-if="progresso !== null" class="mt-2 flex items-center gap-3">
      <div
        class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
        role="progressbar"
        :aria-valuenow="progresso"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div class="h-full rounded-full bg-blue-600" :style="{ width: progresso + '%' }"></div>
      </div>
      <span class="w-10 shrink-0 text-right text-sm font-medium text-slate-700 dark:text-slate-200">{{ progresso }}%</span>
    </div>
  </li>
</template>
