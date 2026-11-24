<script setup>
/** Celula de "Detalhes da capacitacao": icone, rotulo e valor. */
defineProps({
  icon: { type: [Object, Function], required: true },
  rotulo: { type: String, required: true },
  valor: { type: [String, Number], default: null },
});
</script>

<template>
  <div class="flex min-w-0 items-center gap-3 p-4">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
      <component :is="icon" class="h-5 w-5" aria-hidden="true" />
    </span>
    <div class="min-w-0">
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ rotulo }}</p>
      <p class="break-words text-sm font-medium text-slate-900 dark:text-white">
        <slot>{{ valor }}</slot>
      </p>
    </div>
  </div>
</template>
