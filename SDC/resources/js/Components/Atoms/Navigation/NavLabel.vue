<template>
  <span
    :class="[
      'text-[13px] tracking-tight transition-all whitespace-nowrap',
      active
        ? 'text-slate-800 dark:text-white font-bold'
        : 'text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-slate-200 cursor-pointer'
    ]"
  >
    {{ text }}
  </span>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    required: true
  },
  active: {
    type: Boolean,
    default: false
  }
});
</script>
