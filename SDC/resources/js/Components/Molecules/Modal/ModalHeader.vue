<template>
  <div class="flex items-center justify-between gap-3">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h3>
    <!-- Alvo de 40px no toque; o -m-2 devolve o espaco para o icone ficar alinhado. -->
    <button
      type="button"
      class="-m-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-200"
      aria-label="Fechar"
      @click="$emit('close')"
    >
      <XMarkIcon class="h-5 w-5" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
  title: { type: String, required: true },
});
defineEmits(['close']);
</script>
