<template>
  <div class="w-full decretacao-tabs-container">
    <div class="decretacao-tabs-wrapper bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6 relative">
      <nav class="flex gap-1 overflow-x-auto hide-scrollbar" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          :class="getTabClass(tab)"
          :disabled="tab.disabled"
          :aria-disabled="tab.disabled ? 'true' : 'false'"
          :title="tab.disabled ? 'Disponivel apos salvar a aba anterior' : tab.label"
          @click="onTabClick(tab)"
        >
          <span class="whitespace-nowrap">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="ml-1.5 px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0"
            :class="activeTab === tab.id ? 'bg-blue-500/20 text-blue-600 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <div class="decretacao-tabs-content">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  activeTab: {
    type: String,
    default: 'identificacao',
  },
  tabs: {
    type: Array,
    required: true,
    // Cada tab: { id: string, label: string, disabled?: boolean, badge?: number }
  },
});

const emit = defineEmits(['update:activeTab']);

function onTabClick(tab) {
  if (tab.disabled) return;
  if (tab.id === props.activeTab) return;
  emit('update:activeTab', tab.id);
}

function getTabClass(tab) {
  const base =
    'px-3 py-3 sm:px-4 sm:py-2.5 rounded-lg font-medium text-sm transition-all duration-200 flex items-center gap-1.5 sm:gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 select-none whitespace-nowrap min-w-[3rem] sm:min-w-0 justify-center sm:justify-start';

  if (tab.disabled) {
    return `${base} text-slate-400 dark:text-slate-600 cursor-not-allowed opacity-60`;
  }

  if (props.activeTab === tab.id) {
    return `${base} cursor-pointer text-blue-600 dark:text-blue-400 bg-white dark:bg-blue-500/10 border-b-2 border-blue-500 dark:border-blue-400 shadow-sm dark:shadow-none`;
  }

  return `${base} cursor-pointer text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-700/50`;
}
</script>

<style scoped>
.decretacao-tabs-container {
  width: 100%;
  position: relative;
}
.decretacao-tabs-wrapper {
  position: relative;
  z-index: 10;
}
.decretacao-tabs-content {
  position: relative;
  z-index: 1;
  width: 100%;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
