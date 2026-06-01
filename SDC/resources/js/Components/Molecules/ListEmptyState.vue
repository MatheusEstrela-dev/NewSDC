<script setup>
defineProps({
  icon: { type: [Object, Function], default: null },
  title: { type: String, default: 'Nenhum registro encontrado' },
  helper: { type: String, default: 'Tente ajustar os filtros de busca' },
});
</script>

<template>
  <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <component
      :is="icon"
      v-if="icon"
      class="w-10 h-10 text-slate-400 dark:text-slate-500 mb-3"
    />
    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      class="w-10 h-10 text-slate-400 dark:text-slate-500 mb-3"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="1.5"
        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
      />
    </svg>
    <p class="font-medium text-slate-700 dark:text-slate-300 text-sm">
      {{ title }}
    </p>
    <p v-if="helper" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
      {{ helper }}
    </p>
  </div>
</template>
