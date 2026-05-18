<template>
  <section class="list-surface">
    <header v-if="title || $slots.header" class="list-surface-header">
      <slot name="header">
        <div class="min-w-0 flex-1">
          <h3 class="list-surface-title">
            <component
              :is="icon"
              v-if="icon"
              class="h-4 w-4 shrink-0 text-primary-400 sm:h-5 sm:w-5"
            />
            <span class="truncate">{{ title }}</span>
          </h3>
          <p v-if="subtitle" class="mt-0.5 hidden text-xs text-slate-400 sm:block">
            {{ subtitle }}
          </p>
        </div>

        <span v-if="count !== null" class="list-surface-count">
          {{ count }}
        </span>
      </slot>
    </header>

    <div class="min-w-0">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  count: {
    type: [Number, String],
    default: null,
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
});
</script>

<style scoped>
.list-surface {
  @apply overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700/50 dark:bg-slate-800/50 sm:rounded-xl;
}

.list-surface-header {
  @apply flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700/50 dark:bg-slate-800/70 sm:px-4 sm:py-4 md:px-6;
}

.list-surface-title {
  @apply flex items-center gap-1.5 truncate text-sm font-bold text-slate-800 dark:text-slate-100 sm:gap-2 sm:text-base;
}

.list-surface-count {
  @apply ml-2 shrink-0 rounded-lg border border-blue-200 bg-blue-100 px-2 py-1 text-xs font-bold text-blue-600 dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-300 sm:px-3 sm:py-1.5;
}
</style>
