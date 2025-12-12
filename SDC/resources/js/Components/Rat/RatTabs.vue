<template>
  <div class="w-full rat-tabs-container">
    <!-- Seletor de Abas -->
    <!-- Pipeline validation: Layout das abas corrigido e otimizado -->
    <div class="rat-tabs-wrapper bg-slate-800/30 rounded-xl p-1.5 mb-6">
      <nav class="flex gap-1 overflow-x-auto hide-scrollbar" aria-label="Tabs">
        <button
          v-for="tab in visibleTabs"
          :key="tab.id"
          @click="$emit('tab-change', tab.id)"
          :class="getTabClass(tab.id)"
          type="button"
        >
          <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
          <span class="whitespace-nowrap">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="ml-1.5 px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0"
            :class="activeTab === tab.id ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-700 text-slate-400'"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Conteúdo das Abas -->
    <div class="rat-tabs-content">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  activeTab: {
    type: Number,
    default: 1,
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['tab-change']);

const visibleTabs = computed(() => {
  return props.tabs.filter(tab => !tab.hidden);
});

function getTabClass(tabId) {
  const baseClass =
    'px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 flex items-center gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 cursor-pointer select-none relative whitespace-nowrap';

  if (props.activeTab === tabId) {
    return `${baseClass} text-blue-400 bg-blue-500/10 border-b-2 border-blue-400`;
  }

  return `${baseClass} text-slate-400 hover:text-white hover:bg-slate-700/50`;
}
</script>

<style scoped>
.rat-tabs-container {
  width: 100%;
  position: relative;
}

.rat-tabs-wrapper {
  position: relative;
  z-index: 10;
}

.rat-tabs-content {
  position: relative;
  z-index: 1;
  width: 100%;
}

/* Garantir que os botões das abas não quebrem */
.rat-tabs-wrapper nav {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
}

.rat-tabs-wrapper button {
  flex-shrink: 0;
  min-width: fit-content;
}
</style>

