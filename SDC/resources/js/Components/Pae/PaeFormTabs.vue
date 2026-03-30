<template>
  <div class="bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Seções do formulário RAT">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('tab-change', tab.id)"
        :class="getTabClass(tab.id)"
        type="button"
      >
        <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
        <span class="whitespace-nowrap">{{ tab.label }}</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
const props = defineProps({
  activeTab: {
    type: Number,
    default: 1,
  },
  tabs: {
    type: Array,
    required: true,
  },
});

defineEmits(['tab-change']);

function getTabClass(tabId) {
  const base =
    'px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg font-medium text-xs sm:text-sm transition-all duration-200 flex items-center gap-1.5 sm:gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 cursor-pointer select-none';

  if (props.activeTab === tabId) {
    return `${base} text-blue-400 bg-blue-500/10 border-b-2 border-blue-400`;
  }

  return `${base} text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700/50`;
}
</script>
