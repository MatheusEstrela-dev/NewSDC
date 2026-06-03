<template>
  <button
    @click="handleClick"
    class="flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800/30 dark:hover:bg-slate-800/50 dark:border-slate-700/40 dark:text-slate-400 dark:hover:text-white rounded-lg transition-colors active:scale-95 group shadow-sm"
  >
    <ArrowLeftIcon class="w-3.5 h-3.5 transition-colors" />
    <span class="text-[13px] font-medium transition-colors">
      Voltar
    </span>
  </button>
</template>

<script setup>
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const emit = defineEmits(['click']);

const handleClick = () => {
  emit('click');
};
</script>
