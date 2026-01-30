<template>
  <div
    :class="[
      'flex items-center px-3 py-1.5 transition-colors',
      !isLast ? 'border-r border-slate-200 dark:border-slate-700/50' : ''
    ]"
  >
    <slot />
  </div>
</template>

<script setup>
defineProps({
  isLast: {
    type: Boolean,
    default: false
  }
});
</script>
