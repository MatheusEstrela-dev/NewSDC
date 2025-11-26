<template>
  <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 overflow-hidden">
    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-700 bg-slate-800/50">
      <h3 class="text-base sm:text-lg font-semibold text-white flex items-center gap-2 break-words">
        <span class="w-1 h-6 bg-blue-500 rounded-full flex-shrink-0"></span>
        <span>{{ title }}</span>
      </h3>
    </div>
    <div class="p-4 sm:p-6">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>

