<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';

defineProps({
  titulo: { type: String, required: true },
  total: { type: Number, default: null },
});
</script>

<template>
  <div class="mb-4 flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-2.5 dark:border-slate-700/60 dark:bg-slate-800/60">
    <span class="h-5 w-1 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true" />
    <Heading :level="2" class="!text-base !font-semibold">{{ titulo }}</Heading>
    <span
      v-if="total !== null"
      class="ml-auto rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300"
    >
      {{ total }}
    </span>
  </div>
</template>
