<script setup>
defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], default: null },
  subtitle: { type: String, default: '' },
  count: { type: Number, default: null },
  iconClass: { type: String, default: 'text-blue-500' },
});
</script>

<template>
  <div class="bg-white dark:bg-slate-800/60 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden backdrop-blur-sm">
    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-slate-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <component :is="icon" v-if="icon" :class="['w-5 h-5 flex-shrink-0', iconClass]" />
        <div>
          <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">
            {{ title }}
            <span v-if="count !== null" class="text-slate-500 dark:text-slate-400 font-normal">
              ({{ count }} {{ count === 1 ? 'registro' : 'registros' }})
            </span>
          </h3>
          <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {{ subtitle }}
          </p>
        </div>
      </div>
      <div v-if="$slots['header-actions']" class="flex items-center gap-2">
        <slot name="header-actions" />
      </div>
    </div>
    <div class="relative overflow-x-auto">
      <slot />
    </div>
  </div>
</template>
