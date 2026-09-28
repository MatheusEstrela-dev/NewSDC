<template>
  <li class="rounded-xl border border-slate-200 p-4 dark:border-slate-700/50">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <p class="font-semibold text-slate-900 dark:text-slate-100">{{ item.rotulo }}</p>
      <time class="text-xs text-slate-500">{{ formatarDataHora(item.created_at) }}</time>
    </div>
    <p v-if="item.detalhes" class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.detalhes }}</p>
    <p v-if="item.autor" class="mt-2 text-xs text-slate-500">Por: {{ item.autor }}</p>
  </li>
</template>

<script setup>
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({ item: { type: Object, required: true } });
</script>
