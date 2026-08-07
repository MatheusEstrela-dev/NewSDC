<template>
  <div class="w-full treinamento-tabs-container">
    <div class="treinamento-tabs-wrapper bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6 relative">
      <div
        v-if="showLeftFade"
        class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-slate-100 dark:from-slate-800/80 to-transparent z-10 pointer-events-none rounded-l-xl"
      ></div>
      <div
        v-if="showRightFade"
        class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-slate-100 dark:from-slate-800/80 to-transparent z-10 pointer-events-none rounded-r-xl"
      ></div>

      <nav
        ref="tabsNav"
        class="flex gap-1 overflow-x-auto hide-scrollbar scroll-smooth snap-x snap-mandatory"
        aria-label="Tabs"
        @scroll="handleScroll"
      >
        <button
          v-for="tab in visibleTabs"
          :key="tab.id"
          type="button"
          class="snap-start"
          :class="getTabClass(tab.id)"
          @click="emit('tab-change', tab.id)"
        >
          <component :is="tab.icon" class="w-5 h-5 sm:w-4 sm:h-4 flex-shrink-0" />
          <span class="hidden sm:inline whitespace-nowrap">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="ml-1 sm:ml-1.5 px-1.5 sm:px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 min-w-[1.25rem] text-center"
            :class="activeTab === tab.id ? 'bg-blue-500/20 text-blue-600 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <div class="treinamento-tabs-content">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  activeTab: {
    type: [String, Number],
    default: null,
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['tab-change']);

const tabsNav = ref(null);
const showLeftFade = ref(false);
const showRightFade = ref(false);

const visibleTabs = computed(() => props.tabs.filter((tab) => !tab.hidden));

function getTabClass(tabId) {
  const baseClass =
    'px-3 py-3 sm:px-4 sm:py-2.5 rounded-lg font-medium text-sm transition-all duration-200 flex items-center gap-1.5 sm:gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 select-none relative whitespace-nowrap min-w-[3rem] sm:min-w-0 justify-center sm:justify-start cursor-pointer';

  if (props.activeTab === tabId) {
    return `${baseClass} text-blue-600 dark:text-blue-400 bg-white dark:bg-blue-500/10 border-b-2 border-blue-500 dark:border-blue-400 shadow-sm dark:shadow-none`;
  }

  return `${baseClass} text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-700/50`;
}

function handleScroll() {
  if (!tabsNav.value) return;
  const { scrollLeft, scrollWidth, clientWidth } = tabsNav.value;
  showLeftFade.value = scrollLeft > 10;
  showRightFade.value = scrollLeft < scrollWidth - clientWidth - 10;
}

function checkOverflow() {
  if (!tabsNav.value) return;
  const { scrollWidth, clientWidth } = tabsNav.value;
  if (scrollWidth > clientWidth) {
    handleScroll();
  } else {
    showLeftFade.value = false;
    showRightFade.value = false;
  }
}

onMounted(() => {
  checkOverflow();
  window.addEventListener('resize', checkOverflow);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkOverflow);
});
</script>

<style scoped>
.treinamento-tabs-container {
  width: 100%;
  position: relative;
}

.treinamento-tabs-wrapper {
  position: relative;
  z-index: 10;
}

.treinamento-tabs-content {
  position: relative;
  z-index: 1;
  width: 100%;
}

.treinamento-tabs-wrapper nav {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  -webkit-overflow-scrolling: touch;
}

.treinamento-tabs-wrapper button {
  flex-shrink: 0;
  min-width: fit-content;
}

.hide-scrollbar {
  scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

@media (max-width: 639px) {
  .treinamento-tabs-wrapper nav {
    gap: 0.25rem;
    padding: 0.25rem;
  }

  .treinamento-tabs-wrapper button {
    min-width: 3rem;
    min-height: 2.75rem;
  }
}

.snap-x {
  scroll-snap-type: x mandatory;
}

.snap-mandatory {
  scroll-snap-type: x mandatory;
}

.snap-start {
  scroll-snap-align: start;
}
</style>
