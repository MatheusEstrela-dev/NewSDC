<template>
  <button
    @click="handleClick"
    class="flex items-center gap-2 px-3 py-1.5 bg-slate-800/30 hover:bg-slate-800/50 border border-slate-700/40 rounded-lg transition-all active:scale-95 group shadow-sm"
  >
    <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-400 group-hover:text-white transition-colors" />
    <span class="text-[13px] font-medium text-slate-300 group-hover:text-white transition-colors">
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
