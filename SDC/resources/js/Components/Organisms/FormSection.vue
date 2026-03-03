<template>
  <div :class="['form-section', collapsible && 'collapsible']">
    <div
      v-if="title"
      :class="[
        'form-section-header flex items-center gap-3 mb-4',
        collapsible && 'cursor-pointer'
      ]"
      @click="collapsible && toggleCollapse()"
    >
      <div v-if="icon" class="form-section-icon text-blue-500 dark:text-blue-400">
        <component :is="icon" class="w-5 h-5" />
      </div>
      <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200">
        {{ title }}
      </h3>
      <span v-if="subtitle" class="text-sm text-slate-500">
        {{ subtitle }}
      </span>
      <div v-if="collapsible" class="ml-auto">
        <svg
          :class="['w-5 h-5 text-slate-500 dark:text-slate-400 transition-transform', collapsed && 'rotate-180']"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>
    <div v-show="!collapsed" :class="['form-section-content', gridClass]">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  collapsible: {
    type: Boolean,
    default: false,
  },
  defaultCollapsed: {
    type: Boolean,
    default: false,
  },
  cols: {
    type: [Number, String],
    default: 2,
  },
});

const collapsed = ref(props.defaultCollapsed);

function toggleCollapse() {
  collapsed.value = !collapsed.value;
}

const gridClass = computed(() => {
  const colsMap = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
  };
  return `grid gap-x-6 gap-y-4 ${colsMap[props.cols] || colsMap[2]}`;
});
</script>

<style scoped>
.form-section {
  @apply p-5 md:p-6 lg:p-8 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 mb-4 shadow-sm dark:shadow-none;
}

.form-section.collapsible .form-section-header:hover {
  @apply bg-slate-50 dark:bg-slate-700/30 -mx-5 md:-mx-6 lg:-mx-8 -mt-5 md:-mt-6 lg:-mt-8 px-5 md:px-6 lg:px-8 pt-5 md:pt-6 lg:pt-8 pb-4 mb-0 rounded-t-xl transition-colors;
}
</style>
